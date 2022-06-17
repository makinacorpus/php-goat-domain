<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Collection;

/**
 * Collection of items.
 */
interface Collection extends \Traversable, \Countable, \ArrayAccess
{
    /**
     * Is the given collection numerically indexed.
     */
    public function isNumericallyIndexed(): bool;

    /**
     * Get first item matching the given condition.
     */
    public function first(?callable $filter = null) /* : mixed */;

    /**
     * Get last item matching the given condition.
     */
    public function last(?callable $filter = null) /* : mixed */;

    /**
     * Sort collection using the given filter.
     */
    // public function filter(callable $filter = null): Collection;

    /**
     * Get first item matching the given condition.
     */
    // public function filter(callable $filter = null): Collection;

    /**
     * Map items using the given callback.
     */
    // public function map(callable $function = null): Collection;

    /**
     * Traverse the collection in reversed order.
     */
    // public function reverse(callable $function = null): Collection;

    /**
     * Get item at position or matching criteria.
     *
     * This method will raise exceptions if item is not found.
     *
     * @param int|string|callable $filter
     *   If $criteria is an int or string, it will return the value at the given index.
     *   If $criteria is a callable, it will iterate over all items and return
     *   the first one matching.
     */
    public function get(/* int|string|callable */ $filter) /* : mixed */;

    /**
     * Alias of get() that returns null instead of an exception if nothing found.
     *
     * @param int|string|callable $filter
     *   If $criteria is an int or string, return the value at the given index.
     *   If $criteria is a callable, iterate over all items and return
     *   the first one matching.
     */
    public function find(/* int|string|callable */ $filter) /* : mixed */;

    /**
     * Does the element exists.
     *
     * @param callable|mixed $filter
     *   If $criteria is a callable, it will iterate over all items and return
     *   the first one matching.
     *   For any other value type, remove all items that are identical to the
     *   given value.
     */
    public function contains(/* callable|mixed */ $filter): bool;

    /**
     * Does the key exists.
     */
    public function containsKey(/* int|string */ $filter): bool;

    /**
     * Are all item matching.
     */
    public function all(callable $filter): bool;

    /**
     * Are any item matching.
     */
    public function any(callable $filter): bool;
}
