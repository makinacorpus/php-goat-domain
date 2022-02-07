<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

use Goat\Query\DeleteQuery;
use Goat\Query\InsertQuery;
use Goat\Query\Query;
use Goat\Query\UpdateQuery;

/**
 * Default implementation for the writable repository.
 *
 * @codeCoverageIgnore
 * @deprecated
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

    /**
     * {@inheritdoc}
     */
    public function create(array $values)
    {
        $query = $this
            ->createInsert()
            ->values($values)
        ;

        $this->configureQueryForHydrationViaReturning($query);

        $result = $query->execute();

        if (1 < $result->countRows()) {
            throw new EntityNotFoundError(\sprintf("entity counld not be created"));
        }

        return $result->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id, bool $raiseErrorOnMissing = false)
    {
        $query = $this
            ->createDelete(
                $this->expandPrimaryKey($id)
            )
        ;

        // @todo deal with runner that don't support returning
        $this->configureQueryForHydrationViaReturning($query);

        $result = $query->execute();

        $affected = $result->countRows();
        if ($raiseErrorOnMissing) {
            if (1 < $affected) {
                throw new EntityNotFoundError(\sprintf("updated entity does not exist"));
            }
            if (1 > $affected) {
                // @codeCoverageIgnoreStart
                // This can only happen with a misconfigured repository, a wrongly built
                // select query, or a deficient database (for example MySQL) that
                // which under circumstances may break ACID properties of your data
                // and allow duplicate inserts into tables.
                throw new EntityNotFoundError(\sprintf("update affected more than one row"));
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
        $query = $this
            ->createUpdate(
                $this->expandPrimaryKey($id)
            )
            ->sets($values)
        ;

        // @todo deal with runner that don't support returning
        $this->configureQueryForHydrationViaReturning($query);

        $result = $query->execute();

        $affected = $result->countRows();
        if (1 < $affected) {
            throw new EntityNotFoundError(\sprintf("updated entity does not exist"));
        }
        if (1 > $affected) {
            // @codeCoverageIgnoreStart
            // This can only happen with a misconfigured repository, a wrongly built
            // select query, or a deficient database (for example MySQL) that
            // which under circumstances may break ACID properties of your data
            // and allow duplicate inserts into tables.
            throw new EntityNotFoundError(\sprintf("update affected more than one row"));
            // @codeCoverageIgnoreEnd
        }

        return $result->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function createUpdate($criteria = null): UpdateQuery
    {
        $update = $this->getRunner()->getQueryBuilder()->update($this->getRelation());

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
        $update = $this->getRunner()->getQueryBuilder()->delete($this->getRelation());

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
        return $this->getRunner()->getQueryBuilder()->insert($this->getRelation());
    }
}
