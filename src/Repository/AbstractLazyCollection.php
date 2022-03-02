<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

/**
 * @codeCoverageIgnore
 */
abstract class AbstractLazyCollection implements \IteratorAggregate, LazyCollection
{
    private $expanded;
    private $fullyExpanded = false;
    private $initializer;
    private $iterator;

    /**
     * {@inheritdoc}
     */
    public function __construct($initializer)
    {
        if (\is_iterable($initializer) || \is_array($initializer)) {
            $this->iterator = $initializer;
        } else if (\is_callable($initializer)) {
            $this->initializer = $initializer;
        } else {
            throw new \InvalidArgumentException(\sprintf(
                "\$initializer must be an iterable or a callable, '%s' given",
                (\is_object($initializer) ? \get_class($initializer) : \gettype($initializer))
            ));
        }
    }

    /**
     * Create iterator and drop initializer
     */
    private function getInternalIterator(): iterable
    {
        if (null !== $this->iterator) {
            return $this->iterator;
        }

        $iterator = \call_user_func($this->initializer);
        $this->initializer = null;

        // @todo error handling ?
        return $this->iterator = (\is_iterable($iterator) ? $iterator : []);
    }

    /**
     * Plug here to do extra initialization.
     */
    protected function initializeItem($item)
    {
        return $item;
    }

    /**
     * Force internal expanded array to be initialized.
     */
    final protected function initialize(): void
    {
        if (!$this->fullyExpanded) {
            $this->expanded = [];
            foreach ($this->getInternalIterator() as $key => $item) {
                $this->expanded[$key] = $this->initializeItem($item);
            }
            $this->iterator = null;
            $this->fullyExpanded = true;
        }
    }

    /**
     * Sort with given callback.
     *
     * @param function(T $a, T $b): int $callback
     *   Where T is the item type.
     */
    final public function sortWith(callable $callback): void
    {
        $this->initialize();

        \uasort($this->expanded, $callback);
    }

    /**
     * {@inheritdoc}
     */
    final public function offsetExists($offset)
    {
        $this->initialize();

        return \array_key_exists($offset, $this->expanded);
    }

    /**
     * {@inheritdoc}
     */
    final public function offsetGet($offset)
    {
        $this->initialize();

        return $this->expanded[$offset] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    final public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException("Lazy collections are readonly");
    }

    /**
     * {@inheritdoc}
     */
    final public function offsetUnset($offset)
    {
        throw new \BadMethodCallException("Lazy collections are readonly");
    }

    /**
     * {@inheritdoc}
     */
    final public function count()
    {
        $this->initialize();

        return \count($this->expanded);
    }

    /**
     * {@inheritdoc}
     */
    final public function getIterator()
    {
        $this->initialize();

        return new \ArrayIterator($this->expanded);
    }

    /**
     * {@inheritdoc}
     */
    final public function unwrap()
    {
        $this->initialize();

        return $this;
    }
}
