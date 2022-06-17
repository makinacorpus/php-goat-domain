<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Hydration;

/**
 * Auto-registration of Hydrator service.
 */
interface RepositoryHydratorAware
{
    /**
     * {@inheritdoc}
     */
    public function setRepositoryHydrator(RepositoryHydrator $repositoryHydrator): void;
}
