<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Definition;

/**
 * Database table name definition.
 *
 * This can be used on a repository class for building its definition.
 *
 * @Annotation
 * @Target({"METHOD","CLASS"})
 * @NamedArgumentConstructor
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DatabaseTable
{
    private string $name;
    private string $alias;
    private string $schema = "public";

    /**
     * @param string $table
     *   "schema.table" or "table" formatted string.
     * @param null|string $schema
     *   If "table" name contains one or more dot characters, use this
     *   parameter to deambiguate the parser and explicit the schema name
     *   instead of letting it parse the $table argument for it. Default
     *   schema will always be "public" if none found.
     */
    public function __construct(string $table, ?string $alias = null, ?string $schema = null)
    {
        if ($schema) {
            $this->schema = $schema;
            $this->name = $table;
        } else if (\strpos($table, '.')) {
            list ($this->schema, $this->name) = \explode('.', $table, 2);
        } else {
            $this->name = $table;
        }
        $this->alias = $alias ?? $this->name;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchema(): string
    {
        return $this->schema;
    }
}
