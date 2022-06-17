<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Error;

/**
 * One or more entities could not be found in the database.
 */
class RepositoryEntityNotFoundError extends \RuntimeException implements RepositoryError
{
    public function __construct(?string $message = null, ?int $code = null, ?\Throwable $previous = null)
    {
        if (!$message) {
            $message = \sprintf("one or more entities are missing from the database");
        }

        parent::__construct($message, $code ?? 0, $previous);
    }
}
