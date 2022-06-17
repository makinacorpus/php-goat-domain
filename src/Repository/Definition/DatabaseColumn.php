<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Definition;

/**
 * Database column to entity property mapping definition.
 *
 * Columns defined using this attribute will be considered as primary table
 * columns, and used for INSERT and UPDATE queries.
 *
 * This can be used on a repository class for building its definition.
 *
 * @Annotation
 * @Target({"METHOD","CLASS"})
 * @NamedArgumentConstructor
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class DatabaseColumn
{
    private string $columnName;
    private ?string $propertyName;
    private ?string $tableName;

    /**
     * @param string $column
     *   Column name. It will be escaped and use as-is.
     * @param string $property
     */
    public function __construct(string $column, ?string $property = null, ?string $table = null)
    {
        $this->propertyName = $property;
        if ($table) {
            $this->tableName = $table;
            $this->columnName = $column;;
        } else if (\strpos($column, '.')) {
            list ($this->tableName, $this->columnName) = \explode('.', $column, 2);
        } else {
            $this->columnName = $column;
            $this->tableName = null;
        }
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName ?? $this->columnName;
    }
}
