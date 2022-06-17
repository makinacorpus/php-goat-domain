<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Error;

/**
 * Mostly happens when something is misinitialized or unconfigured.
 */
class ConfigurationError extends \LogicException implements RepositoryError
{
}
