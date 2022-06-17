<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Definition;

/**
 * Entity class name definition.
 *
 * This can be used on a repository class for building its definition.
 *
 * @Annotation
 * @Target({"METHOD","CLASS"})
 * @NamedArgumentConstructor
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class EntityClassName
{
    private string $className;

    public function __construct(string $name)
    {
        $this->className = $name;
    }

    public function getClassName(): string
    {
        return $this->className;
    }
}
