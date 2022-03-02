<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

/**
 * @codeCoverageIgnore
 */
final class DefaultLazyProperty implements LazyProperty
{
    private $value;
    private $initialized = false;
    private $initializer;

    /**
     * {@inheritdoc}
     */
    public function __construct($initializer)
    {
        if (!\is_callable($initializer)) {
            throw new \InvalidArgumentException(\sprintf(
                "\$initializer must be a callable, '%s' given",
                (\is_object($initializer) ? \get_class($initializer) : \gettype($initializer))
            ));
        }

        $this->initializer = $initializer;
    }

    /**
     * Force internal expanded array to be initialized.
     */
    final protected function initialize(): void
    {
        if (!$this->initialized) {
            $this->value = \call_user_func($this->initializer);
            $this->initialized = true;
            $this->initializer = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    final public function unwrap()
    {
        $this->initialize();

        return $this->value;
    }
}
