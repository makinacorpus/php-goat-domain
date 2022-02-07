<?php

declare(strict_types=1);

namespace Goat\Domain\Repository;

/**
 * One or more entities could not be found in the database.
 *
 * @codeCoverageIgnore
 * @deprecated
 */
class EntityNotFoundError extends \RuntimeException
{
    /**
     * Default constructor
     *
     * @param string $message
     * @param int $code
     * @param \Throwable $previous
     */
    public function __construct(?string $message = null, ?int $code = null, ?\Throwable $previous = null)
    {
        if (!$message) {
            $message = \sprintf("one or more entities are missing from the database");
        }

        parent::__construct($message, $code ?? 0, $previous);
    }
}
