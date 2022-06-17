<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Collection;

/**
 * Collection of items that can be muted.
 */
interface MutableCollection extends Collection
{
    /**
     * Append one or more items.
     */
    public function add(/* mixed */ ...$value): void;

    /**
     * Prepend one or more items.
     */
    public function prepend(/* mixed  */ ...$value): void;

    /**
     * Remove all items from collection.
     */
    public function clear(): void;

    /**
     * Set item at given key. Replace existing one in case it exist.
     */
    public function set(/* int|string */ $key, /* mixed */ $value): void;

    /**
     * Remove item(s) at position or matching criteria.
     *
     * @param callable|mixed $filter
     *   If $criteria is a callable, it will iterate over all items and return
     *   the first one matching.
     *   For any other value type, remove all items that are identical to the
     *   given value.
     */
    public function remove(/* callable|mixed */ $filter): int;

    /**
     * Remove item at the given key and return it.
     */
    public function removeAt(/* int|string */ $key) /* : mixed */;

    /**
     * Has this collection muted since initialization.
     */
    public function isModified(): bool;
}
