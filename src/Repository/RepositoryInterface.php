<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

use Goat\Runner\ResultIterator;

/**
 * Maps immutable entities on SQL projections, and provides a set of utilities
 * to load, filter, paginate them.
 *
 * Insertion, update and delete should happen at the table level, and will not
 * be handled by the repository interface.
 *
 * @codeCoverageIgnore
 */
interface RepositoryInterface
{
    /**
     * Get entity class name
     */
    public function getClassName(): string;

    /**
     * Create an instance from given values without persisting the entity
     *
     * @param mixed[] $values
     *   The instance values
     *
     * @return mixed
     *   The created instance
     */
    public function createInstance(array $values);

    /**
     * Find a single object
     *
     * @param int|string|int[]|string[] $id
     *   If primary key of the target relation is multiple, you need to pass
     *   here an array of values, if not, you may pass an array with a single
     *   value or the primary key value directly.
     *
     * @throws EntityNotFoundError
     *   If the entity does not exist in database
     *
     * @return mixed
     *   Loaded entity
     */
    public function findOne($id);

    /**
     * Is there objects existing with the given criteria
     *
     * @param mixed $criteria
     *
     * @see \Goat\Domain\Repository\RepositoryQuery::expandCriteria()
     *   For $criteria parameter definition
     */
    public function exists($criteria): bool;

    /**
     * Find all object with the given primary keys
     *
     * @param array $idList
     *   Values are either single values, or array of values, depending on if
     *   the primary key is multiple or not
     * @param bool $raiseErrorOnMissing
     *   If this is set to true, and objects could not be found in the database
     *   this will raise exceptions
     *
     * @throws EntityNotFoundError
     *   If the $raiseErrorOnMissing is set to true and one or more entities do
     *   not exist in database
     *
     * @return mixed[]|ResultIterator
     *   An iterator on the loaded object
     */
    public function findAll(array $idList, bool $raiseErrorOnMissing = false): ResultIterator;

    /**
     * Find a single instance matching criteria, without ordering
     *
     * @param mixed $criteria
     * @param bool $raiseErrorOnMissing
     *   If this is set to true, and objects could not be found in the database
     *   this will raise exceptions
     *
     * @throws EntityNotFoundError
     *   If the $raiseErrorOnMissing is set to true and one or more entities do
     *   not exist in database
     *
     * @see \Goat\Domain\Repository\RepositoryQuery::expandCriteria()
     *   For $criteria parameter definition
     *
     * @return mixed
     *   Loaded entity
     */
    public function findFirst($criteria, bool $raiseErrorOnMissing = false);

    /**
     * Arbitrary query the repository
     *
     * @param mixed $criteria
     *
     * @see \Goat\Domain\Repository\RepositoryQuery::expandCriteria()
     *   For $criteria parameter definition 
     */
    public function query($criteria = null): RepositoryQuery;
}
