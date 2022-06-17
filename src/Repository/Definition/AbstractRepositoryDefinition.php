<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Definition;

use Goat\Domain\Repository\Key;

abstract class AbstractRepositoryDefinition implements RepositoryDefinition
{
    protected ?string $entityClassName = null;
    protected ?Key $databasePrimaryKey = null;
    /** @var DatabaseColumn[] */
    protected array $databaseColumns = [];
    /** @var DatabaseSelectColumn[] */
    protected array $databaseSelectColumns = [];
    protected ?DatabaseTable $databaseTable = null;

    /**
     * {@inheritdoc}
     */
    public function isComplete(): bool
    {
        return $this->entityClassName && $this->databaseTable && $this->databasePrimaryKey && !$this->databasePrimaryKey->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassName(): string
    {
        if (!$this->entityClassName) {
            throw new \LogicException("Entity class name is not set.");
        }
        return $this->entityClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function hasDatabasePrimaryKey(): bool
    {
        return $this->databasePrimaryKey && !$this->databasePrimaryKey->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePrimaryKey(): Key
    {
        if (!$this->databasePrimaryKey || $this->databasePrimaryKey->isEmpty()) {
            throw new \LogicException("Primary key is not set.");
        }
        return $this->databasePrimaryKey;
    }

    /**
     * {@inheritdoc}
     */
    public function hasDatabaseColumns(): bool
    {
        return !empty($this->databaseColumns);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseColumns(): array
    {
        return $this->databaseColumns;
    }

    /**
     * {@inheritdoc}
     */
    public function hasDatabaseSelectColumns(): bool
    {
        return !empty($this->databaseSelectColumns);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseSelectColumns(): array
    {
        return $this->databaseSelectColumns;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName(): DatabaseTable
    {
        if (!$this->databaseTable) {
            throw new \LogicException("Database table is not set.");
        }
        return $this->databaseTable;
    }
}
