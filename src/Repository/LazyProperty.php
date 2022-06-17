<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

/**
 * @deprecated
 *   Will be replaced by a more robust implementation.
 */
interface LazyProperty
{
    /**
     * Get the real value behind.
     */
    public function unwrap();
}
