<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

use Goat\Domain\Repository\Definition\DatabaseColumn;
use Goat\Domain\Repository\Error\RepositoryEntityNotFoundError;
use Goat\Domain\Repository\Repository\AbstractDefinitionRepository;
use Goat\Domain\Repository\Result\GoatQueryRepositoryResult;
use Goat\Query\DeleteQuery;
use Goat\Query\Expression;
use Goat\Query\InsertQueryQuery;
use Goat\Query\InsertValuesQuery;
use Goat\Query\MergeQuery;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use Goat\Query\UpdateQuery;
use Goat\Query\UpsertQueryQuery;
use Goat\Query\UpsertValuesQuery;
use Goat\Query\Where;
use Goat\Query\Expression\ColumnExpression;
use Goat\Query\Expression\TableExpression;
use Goat\Runner\Runner;

/**
 * Table repository is a simple model implementation that works on an arbitrary
 * select query.
 */
class DefaultRepository extends AbstractDefinitionRepository
{
    protected Runner $runner;

    /**
     * Default constructor
     *
     * @param Runner $runner
     * @param string $class
     *   Class that will be hydrated
     * @param string[] $primaryKey
     *   Column names that is the primary key
     * @param string|TableExpression $relation
     *   Relation, if a string, no schema nor alias will be used
     */
    public function __construct(Runner $runner, ?array $primaryKey = null, $table = null, ?string $tableAlias = null)
    {
        parent::__construct($primaryKey, $table, $tableAlias);

        $this->runner = $runner;
    }

    /**
     * {@inheritdoc}
     */
    public final function getRunner(): Runner
    {
        return $this->runner;
    }

    /**
     * {@inheritdoc}
     */
    public function createSelect($criteria = null, bool $withColumns = true): SelectQuery
    {
        $table = $this->getTable();
        $select = $this->getRunner()->getQueryBuilder()->select($table);

        if ($withColumns) {
            $this->configureQueryForHydrationViaSelect($select, $table->getAlias() ?? $table->getName());
        }

        if ($criteria) {
            $select->where(RepositoryQuery::expandCriteria($criteria));
        }

        return $select;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($criteria): bool
    {
        $select = $this
            ->getRunner()
            ->getQueryBuilder()
            ->select($this->getTable())
            ->columnExpression('1')
        ;

        $select->whereExpression(RepositoryQuery::expandCriteria($criteria));

        return (bool)$select->range(1)->execute()->fetchField();
    }

    /**
     * {@inheritdoc}
     */
    public function findOne($id, $raiseErrorOnMissing = true)
    {
        $result = $this
            ->createSelect()
            ->where($this->expandPrimaryKey($id))
            ->range(1, 0)
            ->execute()
        ;

        if ($result->count()) {
            return $result->fetch();
        }

        if ($raiseErrorOnMissing) {
            throw new RepositoryEntityNotFoundError();
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(array $idList, bool $raiseErrorOnMissing = false): RepositoryResult
    {
        $select = $this->createSelect();
        $orWhere = $select->getWhere()->or();

        foreach ($idList as $id) {
            $orWhere->condition($this->expandPrimaryKey($id));
        }

        return new GoatQueryRepositoryResult($select->execute());
    }

    /**
     * {@inheritdoc}
     */
    public function findFirst($criteria, bool $raiseErrorOnMissing = false)
    {
        $result = $this->createSelect($criteria)->range(1, 0)->execute();

        if ($result->count()) {
            return $result->fetch();
        }

        if ($raiseErrorOnMissing) {
            throw new RepositoryEntityNotFoundError();
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function findSome($criteria, int $limit = 100) : RepositoryResult
    {
        return new GoatQueryRepositoryResult(
            $this
                ->createSelect($criteria)
                ->range($limit, 0)
                ->execute()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function query($criteria = null): RepositoryQuery
    {
        return new RepositoryQuery($this->createSelect($criteria));
    }

    /**
     * Expand primary key item
     *
     * @param mixed $values
     */
    protected final function expandPrimaryKey($values): Where
    {
        $definition = $this->getRepositoryDefinition();

        if (!$definition->hasDatabasePrimaryKey()) {
            throw new QueryError("Repository has no primary key defined.");
        }

        $primaryKey = $definition->getDatabasePrimaryKey();

        return $this->expandKey($primaryKey, $values, $this->getTable()->getAlias());
    }

    /**
     * Expand key.
     *
     * @param mixed $values
     */
    protected final function expandKey(Key $key, $values, ?string $tableAlias = null): Where
    {
        if (!$tableAlias) {
            $tableAlias = $this->getTable()->getAlias();
        }
        if (!$values instanceof KeyValue) {
            $values = $key->expandWith($values);
        }

        $ret = new Where();
        foreach (\array_combine($key->getColumnNames(), $values->all()) as $column => $value) {
            // Repository can choose to actually already have prefixed the column
            // primary key using the alias, let's cover this use case too: this
            // might happen if either the original select query do need
            // deambiguation from the start, or if the API user was extra
            // precautionous.
            if (false === \strpos($column, '.')) {
                $ret->condition(new ColumnExpression($column, $tableAlias), $value);
            } else {
                $ret->condition(new ColumnExpression($column), $value);
            }
        }

        return $ret;
    }

    /**
     * Add relation columns to select.
     */
    protected function configureQueryForHydrationViaReturning(Query $query, ?string $tableAlias = null): void
    {
        if (!$query instanceof InsertQueryQuery &&
            !$query instanceof InsertValuesQuery &&
            !$query instanceof UpsertQueryQuery &&
            !$query instanceof UpsertValuesQuery &&
            !$query instanceof MergeQuery &&
            !$query instanceof DeleteQuery &&
            !$query instanceof UpdateQuery
        ) {
            throw new QueryError("Query cannot hold a RETURNING clause.");
        }

        if (!$tableAlias) {
            $table = $this->getTable();
            $tableAlias = $table->getAlias() ?? $table->getName();
        }

        $some = false;
        $definition = $this->getRepositoryDefinition();
        if ($definition->hasDatabaseColumns()) {
            $some = true;
            foreach ($definition->getDatabaseColumns() as $column) {
                \assert($column instanceof DatabaseColumn);
                $columnTableAlias = $column->getTableName() ?? $tableAlias;
                if ($columnTableAlias === $tableAlias) { // We do not support JOIN on returning, yet.
                    $query->returning(new ColumnExpression($column->getColumnName(), $tableAlias), $column->getPropertyName());
                }
            }
        }
        if ($definition->hasDatabaseSelectColumns()) {
            $some = true;
            foreach ($definition->getDatabaseSelectColumns() as $column) {
                \assert($column instanceof DatabaseColumn);
                $columnTableAlias = $column->getTableName() ?? $tableAlias;
                if ($columnTableAlias === $tableAlias) { // We do not support JOIN on returning, yet.
                    $query->returning(new ColumnExpression($column->getColumnName(), $tableAlias), $column->getPropertyName());
                }
            }
        }
        if (!$some) {
            $query->returning(new ColumnExpression('*', $tableAlias));
        }

        $query->setOption('hydrator', $this->getHydrator());
        $query->setOption('types', $this->defineSelectColumnsTypes());
    }

    /**
     * Add relation columns to select.
     */
    protected function configureQueryForHydrationViaSelect(SelectQuery $select, ?string $tableAlias = null): void
    {
        if (!$tableAlias) {
            $table = $this->getTable();
            $tableAlias = $table->getAlias() ?? $table->getName();
        }

        $some = false;
        $definition = $this->getRepositoryDefinition();
        if ($definition->hasDatabaseColumns()) {
            $some = true;
            foreach ($definition->getDatabaseColumns() as $column) {
                \assert($column instanceof DatabaseColumn);
                $columnTableAlias = $column->getTableName() ?? $tableAlias;
                $select->column(new ColumnExpression($column->getColumnName(), $columnTableAlias), $column->getPropertyName());
            }
        }
        if ($definition->hasDatabaseSelectColumns()) {
            $some = true;
            foreach ($definition->getDatabaseSelectColumns() as $column) {
                \assert($column instanceof DatabaseColumn);
                $columnTableAlias = $column->getTableName() ?? $tableAlias;
                $select->column(new ColumnExpression($column->getColumnName(), $columnTableAlias), $column->getPropertyName());
            }
        }
        if (!$some) {
            $select->column(new ColumnExpression('*', $tableAlias));
        }

        $select->setOption('hydrator', $this->getHydrator());
        $select->setOption('types', $this->defineSelectColumnsTypes());
    }

    /**
     * Normalize column for select
     */
    protected function normalizeColumn($column, ?string $relationAlias = null): Expression
    {
        if ($column instanceof Expression) {
            return $column;
        }
        if (false === \strpos($column, '.')) {
            return new ColumnExpression($column, $relationAlias);
        }
        return new ColumnExpression($column);
    }

    /**
     * Reduce given value set based on keys using the current repository defined columns
     */
    protected function reduceValuesToColumns(array $values): array
    {
        $ret = [];
        foreach ($this->getRepositoryDefinition()->getDatabaseColumns() as $column) {
            \assert($column instanceof DatabaseColumn);
            $columnName = $column->getColumnName();
            if (\array_key_exists($columnName, $values)) {
                $ret[$columnName] = $values[$columnName];
            } else {
                // Attempt with property name.
                $propertyName = $column->getPropertyName();
                if (\array_key_exists($propertyName, $values)) {
                    $ret[$columnName] = $values[$propertyName];
                }
            }
        }
        return $ret;
    }

    /**
     * Reduce given value set based on keys using the allowed key filter.
     *
     * @deprecated
     *   This method is wrong.
     */
    protected function reduceValues(array $values, array $allowedKeys): array
    {
        return \array_intersect_key($values, \array_flip($allowedKeys));
    }
}
