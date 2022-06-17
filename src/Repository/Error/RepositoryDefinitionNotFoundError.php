<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Error;

class RepositoryDefinitionNotFoundError extends \InvalidArgumentException implements RepositoryError
{
}
