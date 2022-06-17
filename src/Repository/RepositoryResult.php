<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

interface RepositoryResult extends \Traversable, \Countable
{
    /**
     * Enable multiple iterations.
     *
     * By doing so this result iterator will consume memory by keeping results
     * for its lifetime.
     */
    public function setRewindable($rewindable = true): self;

    /**
     * Get next element and move forward.
     *
     * Fetch usage is discouraged if you have more than one element in the
     * result because it forces the current implementation to create an
     * extra internal iterator.
     *
     * Whenever you have more than one result, simply use foreach() over
     * the result, which will be much, much more efficient.
     *
     * @return mixed
     */
    public function fetch();
}
