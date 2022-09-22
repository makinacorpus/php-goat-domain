<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Result;

use Goat\Domain\Repository\RepositoryResult;

/**
 * For unit testing.
 */
class ArrayRepositoryResult implements RepositoryResult, \IteratorAggregate
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function setRewindable($rewindable = true): self
    {
    }

    /**
     * {@inheritdoc}
     */
    public function fetch()
    {
        $value = \current($this->data);
        \next($this->data);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->data);
    }
}
