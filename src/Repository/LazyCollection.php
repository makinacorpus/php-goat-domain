<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

/**
 * @codeCoverageIgnore
 */
interface LazyCollection extends LazyProperty, \Traversable, \ArrayAccess, \Countable
{
}
