<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Definition;

use Goat\Domain\Repository\Key;

class RepositoryDefinitionBuilder extends AbstractRepositoryDefinition
{
    private bool $locked = false;
    private bool $empty = true;

    private function dieIfLocked(): void
    {
        if ($this->locked) {
            throw new \BadMethodCallException("You cannot modify internals, instance is locked.");
        }
    }

    /**
     * Builder is considered empty if no modification was done.
     */
    public function isEmpty(): bool
    {
        return $this->empty;
    }

    /**
     * Set entity class name.
     *
     * @return $this
     */
    public function setEntityClassName(string $entityClassName): self
    {
        $this->dieIfLocked();

        if ($this->entityClassName) {
            throw new \LogicException("Entity class name is already set.");
        }

        $this->entityClassName = $entityClassName;
        $this->empty = false;

        return $this;
    }

    /**
     * Set database primary key column names.
     *
     * @return $this
     */
    public function setDatabasePrimaryKey(Key $primaryKey): self
    {
        $this->dieIfLocked();

        if ($this->databasePrimaryKey) {
            throw new \LogicException("Database primary key is already set.");
        }
        if ($primaryKey->isEmpty()) {
            throw new \LogicException("Database primary key cannot be empty.");
        }

        $this->databasePrimaryKey = $primaryKey;
        $this->empty = false;

        return $this;
    }

    /**
     * Add database column.
     *
     * @return $this
     */
    public function addDatabaseColumns(DatabaseColumn $databaseColumn): self
    {
        $this->dieIfLocked();

        // @todo Ensure alias does not already exist.
        $this->databaseColumns[] = $databaseColumn;
        $this->empty = false;

        return $this;
    }

    /**
     * Add read-only database columns to entity properties mapping.
     *
     * Those columns will be used for SELECT queries only.
     *
     * @return $this
     */
    public function addDatabaseSelectColumns(DatabaseSelectColumn $databaseSelectColumn): self
    {
        $this->dieIfLocked();

        // @todo Ensure alias does not already exist.
        $this->databaseSelectColumns[] = $databaseSelectColumn;
        $this->empty = false;

        return $this;
    }

    /**
     * Get table and schema name.
     *
     * @return $this
     */
    public function setTableName(DatabaseTable $databaseTable): self
    {
        $this->dieIfLocked();

        if ($this->databaseTable) {
            throw new \LogicException("Database table is already set.");
        }

        $this->databaseTable = $databaseTable;
        $this->empty = false;

        return $this;
    }

    /**
     * Build instance.
     */
    public function build(): RepositoryDefinition
    {
        $this->locked = true;

        return $this;
    }
}
