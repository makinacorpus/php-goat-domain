<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Registry;

use Goat\Domain\Repository\RepositoryInterface;

trait RepositoryRegistryAwareTrait
{
    private ?RepositoryRegistry $repositoryRegistry = null;

    /**
     * {@inheritdoc}
     */
    public function setRepositoryRegistry(RepositoryRegistry $repositoryRegistry): void
    {
        $this->repositoryRegistry = $repositoryRegistry;
    }

    /**
     * Get repository registry.
     */
    protected function getRepositoryRegistry(): RepositoryRegistry
    {
        if (!$this->repositoryRegistry) {
            throw new \BadMethodCallException("Object is not initialized.");
        }

        return $this->repositoryRegistry;
    }

    /**
     * Shortcurt for getRepositoryRegistry()->getRepository
     */
    protected function getRepository(string $name): RepositoryInterface
    {
        return $this->getRepositoryRegistry()->getRepository($name);
    }
}
