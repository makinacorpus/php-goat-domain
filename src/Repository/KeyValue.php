<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

/**
 * Represent an ordered set of column which altogether compose a key.
 * Primary usage of this is for defining and manipulating primary keys.
 */
class KeyValue implements \Countable
{
    /** @var string[] */
    private array $values;

    /** @var mixed|mixed[] */
    public function __construct($values)
    {
        if (\is_array($values)) {
            $this->values = $values;
        } else if (\is_iterable($values)) {
            $this->values = \iterator_to_array($values);
        } else {
            $this->values = [$values];
        }
    }

    /**
     * Get first value in key value.
     *
     * @return mixed
     */
    public function first() /*: mixed */
    {
        foreach ($this->values as $value) {
            return $value;
        }
        return null;
    }

    /**
     * Is this key value empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->values);
    }

    /**
     * Get all values in key value.
     *
     * @return mixed[]
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * Get string representation of key.
     */
    public function toString(): string
    {
        return '(' . \implode(',', $this->values) . ')';
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->values);
    }
}
