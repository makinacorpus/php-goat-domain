<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Bridge\Symfony\DependencyInjection;

use Goat\Domain\Repository\RepositoryInterface;
use Goat\Domain\Repository\Definition\RepositoryDefinition;
use Goat\Domain\Repository\Registry\RepositoryRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ContainerRepositoryRegistry implements RepositoryRegistry
{
    private ServiceLocator $serviceLocator;

    public function __construct(?ServiceLocator $serviceLocator = null)
    {
        $this->serviceLocator = $serviceLocator ?? new ServiceLocator([]);
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryDefinition(string $name): RepositoryDefinition
    {
        $serviceId = $name . '.definition';

        if ($this->serviceLocator->has($serviceId)) {
            return $this->serviceLocator->get($serviceId);
        }

        throw new \InvalidArgumentException(\sprintf("Definition for repository '%s' does not exist", $name));
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository(string $name): RepositoryInterface
    {
        $serviceId = $name;

        if ($this->serviceLocator->has($serviceId)) {
            return $this->serviceLocator->get($serviceId);
        }

        throw new \InvalidArgumentException(\sprintf("Repository '%s' does not exist", $name));
    }
}
