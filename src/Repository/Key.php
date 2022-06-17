<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

use Goat\Query\QueryError;

/**
 * Represent an ordered set of column which altogether compose a key.
 * Primary usage of this is for defining and manipulating primary keys.
 */
class Key implements \Countable
{
    /** @var string[] */
    private array $columns;

    /** @var string|string[] */
    public function __construct($columns)
    {
        if (\is_string($columns)) {
            $this->columns = [$columns];
        } else if (\is_array($columns)) {
            $this->columns = [];
            foreach ($columns as $index => $column) {
                if (!\is_string($column)) {
                    throw new \InvalidArgumentException(\sprintf("\$columns value %s must be a string.", $index));
                }
                $this->columns[] = $column;
            }
        } else {
            throw new \InvalidArgumentException("\$columns parameter must be a string or an array of string.");
        }
    }

    /**
     * Expand key values from values given.
     */
    public function expandWith($values): KeyValue
    {
        if (!\is_array($values)) {
            $values = [$values];
        }
        if (\count($values) !== $this->count()) {
            throw new QueryError(\sprintf("Column count mismatch between key columns and user input, expect in order: %s.", $this->toString()));
        }

        return new KeyValue($values);
    }

    /**
     * Extract this key from the given value array.
     */
    public function extractFrom(array $values): KeyValue
    {
        $ret = [];
        foreach ($this->columns as $propertyName) {
            if (!\array_key_exists($propertyName, $values)) {
                throw new QueryError(\sprintf("Property '%s' of key %s does not exists in values.", $propertyName, $this->toString()));
            }
            $ret[] = $values[$propertyName];
        }
        return new KeyValue($ret);
    }

    /**
     * Is this key empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->columns);
    }

    /**
     * Get key column names.
     *
     * @return string[]
     */
    public function getColumnNames(): array
    {
        return $this->columns;
    }

    /**
     * Get string representation of key.
     */
    public function toString(): string
    {
        return '(' . \implode(',', $this->columns) . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->columns);
    }
}
