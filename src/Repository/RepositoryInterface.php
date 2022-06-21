<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

use Goat\Domain\Repository\Definition\RepositoryDefinition;
use Goat\Domain\Repository\Error\RepositoryEntityNotFoundError;
use Goat\Query\SelectQuery;
use Goat\Query\Expression\TableExpression;
use Goat\Runner\Runner;

/**
 * Maps immutable entities on SQL projections, and provides a set of utilities
 * to load, filter, paginate them.
 *
 * Insertion, update and delete should happen at the table level, and will not
 * be handled by the repository interface.
 *
 * @todo Get rid of ResultIterator in flavor of a custom result object, whose
 *   interface would actually be more or less the same as goat's one.
 */
interface RepositoryInterface
{
    /**
     * Get entity class name.
     *
     * @deprecated
     *   Please use getRepositoryDefinition() instead.
     */
    public function getClassName(): string;

    /**
     * Get runner.
     *
     * @internal
     *   Will work only for goat-query implementations.
     * @deprecated
     *   This should not be a public API since it's implementation dependant.
     */
    public function getRunner(): Runner;

    /**
     * Get table.
     *
     * @internal
     *   Will work only for goat-query implementations.
     * @deprecated
     *   This should not be a public API since it's implementation dependant.
     */
    public function getTable(): TableExpression;

    /**
     * Create a select query based upon this repository definition.
     *
     * @param mixed $criteria
     * @param bool $withColumns
     *
     * @internal
     *   Will work only for goat-query implementations.
     * @deprecated
     *   This should not be a public API since it's implementation dependant.
     * @see \Goat\Domain\Repository\RepositoryQuery::expandCriteria()
     *   For parameter definition.
     */
    public function createSelect($criteria = null, bool $withColumns = true): SelectQuery;

    /**
     * Get repository definition.
     */
    public function getRepositoryDefinition(): RepositoryDefinition;

    /**
     * Create an instance from given values without persisting the entity.
     *
     * @param mixed[] $values
     *   The instance values
     *
     * @return mixed
     *   The created instance.
     */
    public function createInstance(array $values);

    /**
     * Find a single object.
     *
     * @param int|string|int[]|string[] $id
     *   If primary key of the target relation is multiple, you need to pass
     *   here an array of values, if not, you may pass an array with a single
     *   value or the primary key value directly.
     *
     * @throws RepositoryEntityNotFoundError
     *   If the entity does not exist in database
     *
     * @return mixed
     *   Loaded entity.
     */
    public function findOne($id);

    /**
     * Is there objects existing with the given criteria.
     *
     * @param mixed $criteria
     *
     * @see \Goat\Domain\Repository\RepositoryQuery::expandCriteria()
     *   For $criteria parameter definition.
     */
    public function exists($criteria): bool;

    /**
     * Find all object with the given primary keys.
     *
     * @param array $idList
     *   Values are either single values, or array of values, depending on if
     *   the primary key is multiple or not
     * @param bool $raiseErrorOnMissing
     *   If this is set to true, and objects could not be found in the database
     *   this will raise exceptions
     *
     * @throws RepositoryEntityNotFoundError
     *   If the $raiseErrorOnMissing is set to true and one or more entities do
     *   not exist in database.
     *
     * @return mixed[]|RepositoryResult
     *   An iterator on the loaded object.
     */
    public function findAll(array $idList, bool $raiseErrorOnMissing = false): RepositoryResult;

    /**
     * Find a single instance matching criteria, without ordering.
     *
     * @param mixed $criteria
     * @param bool $raiseErrorOnMissing
     *   If this is set to true, and objects could not be found in the database
     *   this will raise exceptions.
     *
     * @throws RepositoryEntityNotFoundError
     *   If the $raiseErrorOnMissing is set to true and one or more entities do
     *   not exist in database.
     *
     * @see \Goat\Domain\Repository\RepositoryQuery::expandCriteria()
     *   For $criteria parameter definition..
     *
     * @return mixed
     *   Loaded entity.
     */
    public function findFirst($criteria, bool $raiseErrorOnMissing = false);

    /**
     * Find a a limited number of rows matching the given criteria, without ordering.
     *
     * @param mixed $criteria
     *
     * @see \Goat\Domain\Repository\RepositoryQuery::expandCriteria()
     *   For $criteria parameter definition.
     *
     * @return iterable
     *   Matched rows.
     */
    public function findSome($criteria, int $limit = 100) : RepositoryResult;

    /**
     * Arbitrary query the repository.
     *
     * @param mixed $criteria
     *
     * @see \Goat\Domain\Repository\RepositoryQuery::expandCriteria()
     *   For $criteria parameter definition.
     */
    public function query($criteria = null): RepositoryQuery;
}
