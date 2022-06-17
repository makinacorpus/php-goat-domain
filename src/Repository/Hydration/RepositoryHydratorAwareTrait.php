<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Hydration;

use Goat\Domain\Repository\Error\ConfigurationError;

/**
 * Hydrator service for this whole API.
 */
trait RepositoryHydratorAwareTrait /* implements RepositoryHydratorAware */
{
    private ?RepositoryHydrator $repositoryHydrator = null;

    /**
     * {@inheritdoc}
     */
    public function setRepositoryHydrator(RepositoryHydrator $repositoryHydrator): void
    {
        $this->repositoryHydrator = $repositoryHydrator;
    }

    /**
     * Get hydrator instance.
     */
    protected function getRepositoryHydrator(): RepositoryHydrator
    {
        if (!$this->repositoryHydrator) {
            throw new ConfigurationError("Hydrator is not set, did you forget to call setHydrator() ?");
        }

        return $this->repositoryHydrator;
    }
}
