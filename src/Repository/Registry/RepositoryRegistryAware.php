<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Registry;

interface RepositoryRegistryAware
{
    /**
     * Set repository registry.
     */
    public function setRepositoryRegistry(RepositoryRegistry $repositoryRegistry): void;
}
