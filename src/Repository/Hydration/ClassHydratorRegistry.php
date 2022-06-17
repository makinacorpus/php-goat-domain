<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Hydration;

/**
 * Pluggable external class hydrator registry.
 */
interface ClassHydratorRegistry
{
    /**
     * Get hydrator callback for given class name.
     *
     * Hydrator callback must accept a single value array argument and return
     * the object instance.
     */
    public function getClassHydrator(string $className): callable;
}
