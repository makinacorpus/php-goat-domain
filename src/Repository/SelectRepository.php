<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

use Goat\Query\SelectQuery;
use Goat\Runner\Runner;

/**
 * Extend the default table repository by working with an arbitrary given user
 * select query.
 *
 * @codeCoverageIgnore
 */
class SelectRepository extends DefaultRepository
{
    private $select;

    /**
     * Default constructor
     *
     * @param string $class
     *   Default class to use for hydration
     * @param string[] $primaryKey
     *   Primary key column names
     * @param SelectQuery $select
     *   Select query that loads entities
     * @param string[] $columns
     *   Array of known columns
     */
    public function __construct(Runner $runner, string $class, array $primaryKey, SelectQuery $select, array $columns = [])
    {
        $relation = $select->getRelation();
        if ($schema = $relation->getSchema()) {
            $relationString = $schema.'.'.$relation->getName();
        } else {
            $relationString = $relation->getName();
        }

        // @todo
        //   remove columns parameter, and introspect the select query to find them
        //   all using getAllColumns() instead

        parent::__construct($runner, $class, $primaryKey, $relationString, $relation->getAlias(), $columns);

        $this->select = $select;
    }

    /**
     * {@inheritdoc}
     */
    public function createSelect($criteria = null, bool $withColumns = true) : SelectQuery
    {
        $select = clone $this->select;

        if (!$withColumns) {
            $select->removeAllColumns();
        }
        if ($criteria) {
            $select->whereExpression(RepositoryQuery::expandCriteria($criteria));
        }

        return $select;
    }
}
