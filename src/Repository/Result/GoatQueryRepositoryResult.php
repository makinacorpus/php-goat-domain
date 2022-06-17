<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Result;

use Goat\Domain\Repository\RepositoryResult;
use Goat\Runner\ResultIterator;

class GoatQueryRepositoryResult implements RepositoryResult, \IteratorAggregate
{
    private ResultIterator $decorated;

    public function __construct(ResultIterator $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function setRewindable($rewindable = true): self
    {
        $this->decorated->setRewindable($rewindable);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch()
    {
        return $this->decorated->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->decorated->countRows();
    }
}
