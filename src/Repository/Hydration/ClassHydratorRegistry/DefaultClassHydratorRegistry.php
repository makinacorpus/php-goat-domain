<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Hydration\ClassHydratorRegistry;

use Goat\Domain\Repository\Hydration\ClassHydratorRegistry;

/**
 * Usable but very slow implementation.
 */
class DefaultClassHydratorRegistry implements ClassHydratorRegistry
{
    /**
     * Get hydrator callback for given class name.
     *
     * Hydrator callback must accept a single value array argument and return
     * the object instance.
     */
    public function getClassHydrator(string $className): callable
    {
        throw new \Exception("Not implemented yet.");
    }
}
