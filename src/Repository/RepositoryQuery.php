<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

use Goat\Query\Expression;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use Goat\Query\Where;
use Goat\Runner\QueryPagerResultIterator;
use Goat\Runner\ResultIterator;

final class RepositoryQuery
{
    private $select;

    /**
     * Default constructor
     */
    public function __construct(SelectQuery $query)
    {
        $this->select = $query;
    }

    /**
     * Build where from criteria
     *
     * @param array|\Goat\Query\Expression|\Goat\Query\Where $criteria
     *   This value might be either one of:
     *     - a simple key-value array that will be translated into a where
     *       clause using the AND statement, values can be anything including
     *       Expression or Where instances, if keys are integers, values must
     *       will be set using Where::expression() instead of Where::condition()
     *     - an Expression instance
     *     - an array of Expression instances
     *     - a Where instance
     *
     * @return Where
     */
    public static function expandCriteria($criteria): Where
    {
        if (!$criteria) {
            return new Where();
        }
        if ($criteria instanceof Where) {
            return $criteria;
        }
        if ($criteria instanceof Expression) {
            return (new Where())->expression($criteria);
        }

        if (\is_array($criteria)) {
            $where = new Where();

            foreach ($criteria as $column => $value) {
                if (\is_int($column)) {
                    $where->expression($value);
                } else {
                    // Because repositories might attempt to join with other tables they can
                    // arbitrarily use a table alias for the main relation: user may not know
                    // it, and just use field names here - if no column alias is set,
                    // arbitrarily prefix them with the relation alias.
                    // @todo
                    //   - does it really worth it ?
                    //   - if there is more than one alias, how to deal with
                    //     the fact that user might want to filter using
                    //     another column table ?
                    //   - in the end, if ok with those questions, implement
                    //     it and document it.

                    if (\is_null($value)) {
                        $where->isNull($column);
                    } else {
                        $where->condition($column, $value);
                    }
                }
            }

            return $where;
        }

        throw new QueryError("criteria must be an instance of Where, Expression, or an key-value pairs array where keys are columns names and values are column value");
    }

    /**
     * In most case you should not need to use this, but in case you have very
     * specific SQL clauses to add to the select query, it's legal to do so.
     */
    public function getSelectQuery(): SelectQuery
    {
        return $this->select;
    }

    /**
     * @param mixed $criteria
     *
     * @see \Goat\Domain\Repository\RepositoryQuery::expandCriteria()
     *   For parameter definition
     */
    public function with($criteria): self
    {
        $this->select->whereExpression(RepositoryQuery::expandCriteria($criteria));

        return $this;
    }

    /**
     * Order by
     *
     * @param string|\Goat\Query\Expression $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement
     * @param int|string $order
     *   One of the Query::ORDER_* constants, or case-insensitive 'asc', 'desc' values.
     * @param int $null
     *   Null behavior, nulls first, nulls last, or leave the backend default
     */
    public function orderBy($column, $order = Query::ORDER_ASC, int $null = Query::NULL_IGNORE)
    {
        if (\is_string($order)) {
            $order = \strtolower($order) === 'desc' ? Query::ORDER_DESC : Query::ORDER_ASC;
        } else if (!\is_int($order)) {
            throw new \InvalidArgumentException();
        } else {
            $order = Query::ORDER_DESC === $order ? Query::ORDER_DESC : Query::ORDER_ASC;
        }

        $this->select->orderBy($column, $order, $null);

        return $this;
    }

    /**
     * Execute a count query
     */
    public function count(): int
    {
        return $this
            ->select
            ->getCountQuery()
            ->execute()
            ->fetchField()
        ;
    }

    /**
     * Execute query
     */
    public function execute(): ResultIterator
    {
        return $this->select->execute();
    }

    /**
     * Execute query and fetch paginator
     */
    public function paginate(): QueryPagerResultIterator
    {
        return new QueryPagerResultIterator($this->select);
    }
}
