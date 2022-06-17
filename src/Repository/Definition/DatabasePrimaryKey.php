<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Definition;

use Goat\Domain\Repository\Key;

/**
 * Database primary key column list.
 *
 * Columns may or may not exist in columns definition.
 *
 * This can be used on a repository class for building its definition.
 *
 * @Annotation
 * @Target({"METHOD","CLASS"})
 * @NamedArgumentConstructor
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DatabasePrimaryKey
{
    /**
     * @var string[]
     */
    private Key $primaryKey;

    /**
     * @param string|string[] $columns
     *   Column names.
     * @param string $property
     */
    public function __construct($columns)
    {
        $this->primaryKey = new Key($columns);
    }

    public function getPrimaryKey(): Key
    {
        return $this->primaryKey;
    }
}
