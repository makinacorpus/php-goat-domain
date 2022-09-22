<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

use Goat\Domain\Repository\Error\RepositoryEntityNotFoundError;
use Goat\Query\DeleteQuery;
use Goat\Query\InsertQuery;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\UpdateQuery;
use Goat\Query\Expression\ColumnExpression;

/**
 * Default implementation for the writable repository
 */
class WritableDefaultRepository extends DefaultRepository implements WritableRepositoryInterface
{
    /**
     * Implementors must return a correct returninig expression that will
     * hydrate one or more entities
     */
    protected function addReturningToQuery(Query $query)
    {
        // Default naive implementation, return everything from the affected
        // tables. Please note that it might not work as expected in case there
        // is join statements or a complex from statement, case in which
        // specific repository implementations should implement this.
        // Per default, we don't prefix with the repository relation alias, some
        // fields could be useful to the target entity class, we can't know
        // that without knowing the user's business, so leave it as-is to
        // cover the widest range of use cases possible.
        $query->returning('*');
    }

    protected function appendPrimaryKeyToReturning(Query $query, ?string $tableAlias = null): void
    {
        $definition = $this->getRepositoryDefinition();

        if (!$definition->hasDatabasePrimaryKey()) {
            throw new QueryError("Repository has no primary key defined.");
        }

        $primaryKey = $definition->getDatabasePrimaryKey();

        if (!$tableAlias) {
            $table = $this->getTable();
            $tableAlias = $table->getAlias() ?? $table->getName();
        }

        foreach ($primaryKey->getColumnNames() as $columnName) {
            $query->returning(new ColumnExpression($columnName, $tableAlias));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $values)
    {
        $query = $this
            ->createInsert()
            ->values($values)
        ;

        // As we can't easily perform a join on an insert with returning query,
        // we just return primary key columns and we're gonna find
        // that freshly created instance.
        // INSERT can't alias table, so we need to return table name here,
        // not table alias.
        $this->appendPrimaryKeyToReturning($query, $this->getTable()->getName());

        $result = $query->execute()->setHydrator(fn (array $row) => $row);

        if (1 < $result->countRows()) {
            throw new RepositoryEntityNotFoundError(\sprintf("entity counld not be created"));
        }

        return $this->findOne($result->fetch());
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id, bool $raiseErrorOnMissing = false)
    {
        $query = $this->createDelete($this->expandPrimaryKey($id));

        // As we can't easily perform a left join on an update with returning query,
        // we just return primary key columns and we're gonna find
        // that freshly created instance.
        $this->appendPrimaryKeyToReturning($query);
        $result = $query->execute()->setHydrator(fn (array $row) => $row);

        $affected = $result->countRows();
        if ($raiseErrorOnMissing) {
            if (1 < $affected) {
                throw new RepositoryEntityNotFoundError(\sprintf("updated entity does not exist"));
            }
            if (1 > $affected) {
                // @codeCoverageIgnoreStart
                // This can only happen with a misconfigured repository, a wrongly built
                // select query, or a deficient database (for example MySQL) that
                // which under circumstances may break ACID properties of your data
                // and allow duplicate inserts into tables.
                throw new RepositoryEntityNotFoundError(\sprintf("update affected more than one row"));
                // @codeCoverageIgnoreEnd
            }
        }

        return $result->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $values)
    {
        $query = $this->createUpdate($this->expandPrimaryKey($id))->sets($values);

        // As we can't easily perform a left join on an update with returning query,
        // we just return primary key columns and we're gonna find
        // that freshly created instance.
        $this->appendPrimaryKeyToReturning($query);

        $result = $query->execute()->setHydrator(fn (array $row) => $row);

        $affected = $result->countRows();
        if (1 < $affected) {
            throw new RepositoryEntityNotFoundError(\sprintf("updated entity does not exist"));
        }
        if (1 > $affected) {
            // @codeCoverageIgnoreStart
            // This can only happen with a misconfigured repository, a wrongly built
            // select query, or a deficient database (for example MySQL) that
            // which under circumstances may break ACID properties of your data
            // and allow duplicate inserts into tables.
            throw new RepositoryEntityNotFoundError(\sprintf("update affected more than one row"));
            // @codeCoverageIgnoreEnd
        }

        return $this->findOne($result->fetch());
    }

    /**
     * {@inheritdoc}
     */
    public function createUpdate($criteria = null): UpdateQuery
    {
        $update = $this->getRunner()->getQueryBuilder()->update($this->getTable());

        if ($criteria) {
            $update->whereExpression(RepositoryQuery::expandCriteria($criteria));
        }

        return $update;
    }

    /**
     * {@inheritdoc}
     */
    public function createDelete($criteria = null): DeleteQuery
    {
        $update = $this->getRunner()->getQueryBuilder()->delete($this->getTable());

        if ($criteria) {
            $update->whereExpression(RepositoryQuery::expandCriteria($criteria));
        }

        return $update;
    }

    /**
     * {@inheritdoc}
     */
    public function createInsert(): InsertQuery
    {
        return $this->getRunner()->getQueryBuilder()->insert($this->getTable());
    }
}
