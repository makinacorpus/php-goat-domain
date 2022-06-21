<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Bridge\Symfony;

use Goat\Domain\Repository\RepositoryInterface;
use Goat\Domain\Repository\Bridge\Symfony\DependencyInjection\GoatRepositoryExtension;
use Goat\Domain\Repository\Bridge\Symfony\DependencyInjection\Compiler\RepositoryRegistryRegisterPass;
use Goat\Domain\Repository\Hydration\RepositoryHydrator;
use Goat\Domain\Repository\Hydration\RepositoryHydratorAware;
use Goat\Domain\Repository\Registry\RepositoryRegistry;
use Goat\Domain\Repository\Registry\RepositoryRegistryAware;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @codeCoverageIgnore
 */
final class GoatRepositoryBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        // Repository registry magic.
        $container
            ->registerForAutoconfiguration(RepositoryRegistryAware::class)
            ->addTag('goat.domain.repository.registry.aware')
            ->addMethodCall('setRepositoryRegistry', [new Reference(RepositoryRegistry::class)])
        ;
        $container
            ->registerForAutoconfiguration(RepositoryHydratorAware::class)
            ->addTag('goat.domain.repository.hydrator.aware')
            ->addMethodCall('setRepositoryHydrator', [new Reference(RepositoryHydrator::class)])
        ;
        $container
            ->registerForAutoconfiguration(RepositoryInterface::class)
            ->addTag('goat.domain.repository')
        ;
        $container->addCompilerPass(new RepositoryRegistryRegisterPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new GoatRepositoryExtension();
    }
}
