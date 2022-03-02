<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

use Goat\Query\SelectQuery;
use Goat\Query\Expression\TableExpression;
use Goat\Runner\ResultIterator;
use Goat\Runner\Runner;

/**
 * Maps immutable entities on SQL projections, and provides a set of utilities
 * to load, filter, paginate them.
 *
 * Insertion, update and delete should happen at the table level, and will not
 * be handled by the repository interface.
 *
 * @codeCoverageIgnore
 */
interface GoatRepositoryInterface extends RepositoryInterface
{
    /**
     * Get runner
     */
    public function getRunner(): Runner;

    /**
     * Get entity class name
     */
    public function getClassName(): string;

    /**
     * Get table this repository works on.
     */
    public function getTable(): TableExpression;

    /**
     * Alias of getTable() kept for BC.
     *
     * @deprecated
     */
    public function getRelation(): TableExpression;

    /**
     * Create a select query based upon this repository definition
     *
     * @param mixed $criteria
     * @param bool $withColumns
     *
     * @see \Goat\Domain\Repository\RepositoryQuery::expandCriteria()
     *   For parameter definition
     */
    public function createSelect($criteria = null, bool $withColumns = true): SelectQuery;

    /**
     * Does this repository has columns
     */
    public function hasColumns(): bool;

    /**
     * Get selected relation columns
     *
     * @return string[]
     *   Column names
     */
    public function getColumns(): array;

    /**
     * Does this repository has a primary key defined
     */
    public function hasPrimaryKey(): bool;

    /**
     * Get primary key column count
     */
    public function getPrimaryKeyCount(): int;

    /**
     * Get primary key fields
     *
     * @throws \LogicException
     *   If primary is not defined
     *
     * @return string[]
     */
    public function getPrimaryKey(): array;

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
     * Find a a limited number of rows matching the given criteria, without ordering
     *
     * @param mixed $criteria
     *
     * @see \Goat\Domain\Repository\RepositoryQuery::expandCriteria()
     *   For $criteria parameter definition
     *
     * @return iterable
     *   Matched rows
     */
    public function findSome($criteria, int $limit = 100) : iterable;

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
