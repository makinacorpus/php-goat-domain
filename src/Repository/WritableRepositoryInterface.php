<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

use Goat\Domain\Repository\Error\RepositoryEntityNotFoundError;
use Goat\Query\DeleteQuery;
use Goat\Query\InsertQuery;
use Goat\Query\UpdateQuery;

/**
 * Add update and insert functions to repositories.
 *
 * Be aware that this can only write on a single relation at once.
 */
interface WritableRepositoryInterface extends RepositoryInterface
{
    /**
     * Create one entity from values
     *
     * @param array $values
     *   Values for the entity
     *
     * @return mixed
     *   The created entity
     */
    public function create(array $values);

    /**
     * Update one entity with values
     *
     * @param int|string|int[]|string[] $id
     *   Primary key
     * @param bool $throwIfNotExists
     *   Throw exception when entity does not exists
     *
     * @return mixed
     *   The updated entity
     *
     * @throws RepositoryEntityNotFoundError
     *   If the entity does not exists and $throwIfNotExists is true
     */
    public function delete($id, bool $raiseErrorOnMissing = false);

    /**
     * Update one entity with values
     *
     * @param int|string|int[]|string[] $id
     *   Primary key
     * @param mixed[] $values
     *   New values to set, can be partial
     *
     * @return mixed
     *   The updated entity
     *
     * @throws RepositoryEntityNotFoundError
     *   If the entity does not exists
     */
    public function update($id, array $values);

    /**
     * Create update query
     *
     * @param mixed $criteria
     *
     * @see \Goat\Domain\Repository\DefaultRepository::expandCriteria()
     *   For $criteria parameter definition
     *
     * @return UpdateQuery
     */
    public function createUpdate($criteria = null): UpdateQuery;

    /**
     * Create delete query
     *
     * @param mixed $criteria
     *
     * @see \Goat\Domain\Repository\DefaultRepository::expandCriteria()
     *   For $criteria parameter definition
     *
     * @return UpdateQuery
     */
    public function createDelete($criteria = null): DeleteQuery;

    /**
     * Create insert query with values
     */
    public function createInsert(): InsertQuery;
}
