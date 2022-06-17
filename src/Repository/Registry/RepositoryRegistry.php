<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Registry;

use Goat\Domain\Repository\RepositoryInterface;
use Goat\Domain\Repository\Definition\RepositoryDefinition;

interface RepositoryRegistry
{
    /**
     * Get repository definition.
     *
     * @param string $name
     *   Can be anything of repository class name or handled class name.
     */
    public function getRepositoryDefinition(string $name): RepositoryDefinition;

    /**
     * Get repository.
     *
     * @param string $name
     *   Can be anything of repository class name or handled class name.
     */
    public function getRepository(string $name): RepositoryInterface;
}
