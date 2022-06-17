<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Collection;

/**
 * Array collection that also keeps a lazy load capability.
 */
class ArrayCollection implements MutableCollection, \IteratorAggregate
{
    protected ?array $data = null;
    private ?int $count = null;
    private ?bool $numericallyIndexed = null;
    private bool $hasMuted = false;
    private /* null|iterable|callable */ $initializer = null;
    private /* null|callable */ $objectInitializer = null;

    /**
     * Construct collection.
     *
     * @param null|iterable|array|callable $data
     *   Collection data. If a callable or iterable is provided, the collection
     *   will be lazy-initialized upon first usage. If iterable or callable is
     *   also a \Countable implementation, count() method will not trigger lazy
     *   initialization.
     * @param callable $objectInitializer
     *   A callback that will be applied to any external object added into this
     *   collection. It will not be applied to items given into $data.
     */
    public function __construct(/* null|iterable|array|callable */ $data, ?callable $objectInitializer = null)
    {
        if (null === $data) {
            $this->data = [];
            $this->count = 0;
        } else if (\is_array($data)) {
            $this->data = $data;
            $this->count = \count($data);
        } else if (!\is_callable($data) && !\is_iterable($data)) {
            throw new \InvalidArgumentException(\sprintf(
                "\$data parameter must be an array or an iterable, '%s' given",
                \gettype($data)
            ));
        } else {
            if (\is_countable($data)) {
                $this->count = \count($data);
                if (0 === $this->count) {
                    $this->data = [];
                } else {
                    $this->initializer = $data;
                }
            } else {
                $this->initializer = $data;
            }
        }
        $this->objectInitializer = $objectInitializer;
    }

    /**
     * Initialize iteration.
     *
     * @todo Initialization here is not performant, since it will always
     *   copy the array or iterable initializer.
     */
    final protected function initialize(): void
    {
        if (null !== $this->data) {
            return;
        }

        $numericallyIndexed = null;
        try {
            if (\is_callable($this->initializer)) {
                $iterator = ($this->initializer)();
            } else {
                $iterator = $this->initializer;
            }

            if (null === $iterator) {
                $this->data = [];
            } else if (\is_array($iterator)) {
                $this->data = $iterator;
            } else if (\is_iterable($iterator)) {
                $numericallyIndexed = true;
                foreach ($iterator as $key => $value) {
                    $this->data[$key] = $value;
                    if ($numericallyIndexed && \is_int($key)) {
                        $numericallyIndexed = false;
                    }
                }
            } else {
                throw new \InvalidArgumentException(\sprintf(
                    "\$data callback did not return an iterable value, '%s' returned.",
                    \get_debug_type($iterator)
                ));
            }
        } finally {
            if (null === $this->data) {
                $this->data = [];
            }
            $this->initializer = null;
            $this->count = \count($this->data);

            // The value can still be null here, in case we initialized
            // with an array. isNumericallyIndexed() method is likely to be
            // quite rare, and we skip array iteration and we will lazy
            // initialize the numerical state.
            $this->numericallyIndexed = $numericallyIndexed;
        }
    }

    /**
     * Initialize object upon add.
     */
    protected function initializeItem(/* mixed */ $object)
    {
        if ($this->objectInitializer) {
            return ($this->objectInitializer)($object);
        }
        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Iterator
    {
        $this->initialize();

        return (fn () => yield from $this->data)();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        if (null === $this->count) {
            $this->initialize();
        }
        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function isNumericallyIndexed(): bool
    {
        if (null === $this->numericallyIndexed) {
            $this->initialize();
        }
        return $this->numericallyIndexed;
    }

    /**
     * {@inheritdoc}
     */
    public function first(?callable $filter = null) /* : mixed */
    {
        $this->initialize();

        if (0 === $this->count) {
            return null;
        }

        if (null === $filter) {
             return $this->data[0];
        }

        foreach ($this->data as $item) {
            if ($filter($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function last(?callable $filter = null) /* : mixed */
    {
        $this->initialize();

        if (0 === $this->count) {
            return null;
        }

        if (null === $filter) {
             return $this->data[$this->count - 1];
        }

        foreach (\array_reverse($this->data) as $item) {
            if ($filter($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function get(/* int|string|callable */ $filter) /* : mixed */
    {
        $this->initialize();

        if (\is_int($filter) || \is_string($filter)) {
            // Using $this as a return value seems weird, but it's a pointer
            // to an object that will likely NEVER be in this collection.
            // If we choose to allow null values being valid values in this
            // collection, it's a way to avoid \array_key_exists() call and
            // confusing null with an invalid value altogether.
            $value = $this->data[$filter] ?? $this;

            if ($this === $value) {
                throw new \InvalidArgumentException(\sprintf("Key '%s' does not exist in collection.", $filter));
            }
            return $value;
        }

        if (\is_callable($filter)) {
            foreach ($this->data as $item) {
                if ($filter($item)) {
                    return $item;
                }
            }

            throw new \InvalidArgumentException(\sprintf("Item was not found in collection."));
        }

        throw new \InvalidArgumentException(\sprintf(
            "\$filter must be a callable or int value, '%s' returned.",
            \get_debug_type($filter)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function find(/* int|callable */ $filter) /* : mixed */
    {
        try {
            return $this->get($filter);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function contains(/* callable|mixed */ $filter): bool
    {
        $this->initialize();

        if (\is_callable($filter)) {
            foreach ($this->data as $item) {
                if ($filter($item)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($this->data as $item) {
            if ($item === $filter) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function containsKey(/* int|string */ $filter): bool
    {
        $this->initialize();

        return \array_key_exists($filter, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function all(callable $filter): bool
    {
        $this->initialize();

        if (0 === $this->count) {
            return false;
        }

        foreach ($this->data as $item) {
            if (!$filter($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function any(callable $filter): bool
    {
        $this->initialize();

        foreach ($this->data as $item) {
            if ($filter($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function add(/* mixed */ ...$value): void
    {
        if ($value) {
            $this->initialize();

            try {
                foreach ($value as $item) {
                    $this->data[] = $this->initializeItem($item);
                }
            } finally {
                $this->count = \count($this->data);
                $this->hasMuted = true;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(/* mixed */ ...$value): void
    {
        if ($value) {
            try {
                foreach (\array_reverse($value) as $item) {
                    \array_unshift($this->data, $this->initializeItem($item));
                }
            } finally {
                $this->count = \count($this->data);
                $this->hasMuted = true;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->hasMuted = true;
        $this->data = [];
        $this->count = 0;
        $this->initializer = null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(/* int|string */ $position, /* mixed */ $value): void
    {
        $this->initialize();

        try {
            $this->data[$position] = $this->initializeItem($value);
        } finally {
            $this->count = \count($this->data);
            $this->hasMuted = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove(/* callable|mixed */ $filter): int
    {
        $this->initialize();

        $count = 0;
        $temp = [];

        try {
            if (\is_callable($filter)) {
                foreach ($this->data as $key => $item) {
                    if ($filter($item)) {
                        $count++;
                    } else {
                        $temp[$key] = $item;
                    }
                }
            } else {
                foreach ($this->data as $key => $item) {
                    if ($item === $filter) {
                        $count++;
                    } else {
                        $temp[$key] = $item;
                    }
                }
            }
        } finally {
            if ($count) {
                $this->data = $temp;
                $this->count = \count($this->data);
                $this->hasMuted = true;
            }
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAt(/* int|string */ $key) /* : mixed */
    {
        $value = $this->data[$key] ?? null;

        unset($this->data[$key]);
        $this->count = \count($this->data);
        $this->hasMuted = true;

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function isModified(): bool
    {
        return $this->hasMuted;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists(/* mixed */ $offset): bool
    {
        return $this->exists($offset);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetGet(/* mixed */ $offset) /* : mixed */
    {
        return $this->find($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet(/* mixed */ $offset, /* mixed */ $value): void
    {
        if (null === $offset) {
            $this->add($value);
        } else {
            $this->set($offset, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset(/* mixed */ $offset): void
    {
        $this->removeAt($offset);
    }
}
