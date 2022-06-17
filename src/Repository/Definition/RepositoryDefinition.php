<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Definition;

use Goat\Domain\Repository\Key;

interface RepositoryDefinition
{
    /**
     * Is this definition complete.
     */
    public function isComplete(): bool;

    /**
     * Get entity class name.
     */
    public function getEntityClassName(): string;

    /**
     * Does this repository has a primary key.
     */
    public function hasDatabasePrimaryKey(): bool;

    /**
     * Get database primary key.
     */
    public function getDatabasePrimaryKey(): Key;

    /**
     * Does this repository has columns.
     */
    public function hasDatabaseColumns(): bool;

    /**
     * Get database columns to entity properties mapping.
     *
     * Those columns will be used for SELECT, INSERT and UPDATE queries.
     *
     * @return DatabaseColumn[]
     */
    public function getDatabaseColumns(): array;

    /**
     * Does this repository has read-only columns.
     */
    public function hasDatabaseSelectColumns(): bool;

    /**
     * Get read-only database columns to entity properties mapping.
     *
     * Those columns will be used for SELECT queries only.
     *
     * @return DatabaseSelectColumn[]|DatabaseColumn[]
     */
    public function getDatabaseSelectColumns(): array;

    /**
     * Get table and schema name.
     */
    public function getTableName(): DatabaseTable;
}
