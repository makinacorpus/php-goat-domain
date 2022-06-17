<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Bridge\Symfony\DependencyInjection\Compiler;

use Goat\Bridge\Symfony\DependencyInjection\ContainerRepositoryRegistry;
use Goat\Domain\Repository\Definition\ArrayRepositoryDefinition;
use Goat\Domain\Repository\Definition\DefinitionLoader;
use Goat\Domain\Repository\Error\RepositoryDefinitionNotFoundError;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class RepositoryRegistryRegisterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = [];
        foreach ($container->findTaggedServiceIds('goat.domain.repository', true) as $serviceId => $attributes) {
            $definition = $container->getDefinition($serviceId);

            $class = $definition->getClass();
            if (!$reflectionClass = $container->getReflectionClass($class)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" used for service "%s" cannot be found.', $class, $serviceId));
            }

            $realClass = $reflectionClass->getName();
            $entityClassName = null;

            // Attempt to load a definition from attributes.
            try {
                $definitionLoader = new DefinitionLoader();
                $repositoryDefinition = $definitionLoader->loadDefinition($realClass);
                $entityClassName = $repositoryDefinition->getEntityClassName();

                if (!$repositoryDefinition->isComplete()) {
                    throw new InvalidArgumentException(\sprintf('Repository definition for class "%s" used for service "%s" using annotations or attributes is incomplete.', $class, $serviceId));
                }

                // Register definition services.
                $definitionServiceId = $serviceId . '.definition';
                $definitionService = new Definition();
                $definitionService->setClass(ArrayRepositoryDefinition::class);
                $definitionService->setArguments([ArrayRepositoryDefinition::toArray($repositoryDefinition)]);
                $container->setDefinition($definitionServiceId, $definitionService);

                $services[$class . '.definition'] = new Reference($definitionServiceId);
                $services[$entityClassName . '.definition'] = new Reference($definitionServiceId);
                if ($serviceId !== $class) {
                    $services[$serviceId . '.definition'] = new Reference($definitionServiceId);
                }
                if ($class !== $realClass) {
                    $services[$realClass . '.definition'] = new Reference($definitionServiceId);
                }
            } catch (RepositoryDefinitionNotFoundError $e) {
                // Legacy repository, let it be.
            }

            $services[$class] = new Reference($serviceId);
            if ($entityClassName) {
                $services[$entityClassName] = new Reference($serviceId);
            }
            if ($serviceId !== $class) {
                $services[$serviceId] = new Reference($serviceId);
            }
            if ($class !== $realClass) {
                $services[$realClass] = new Reference($serviceId);
            }
        }

        $containerLocator = $container->getDefinition(ContainerRepositoryRegistry::class);
        $containerLocator->setArgument(0, ServiceLocatorTagPass::register($container, $services));

        foreach ($container->findTaggedServiceIds('goat.domain.repository.registry.aware', true) as $serviceId => $attributes) {
            $container
                ->getDefinition($serviceId)
                ->addMethodCall('setRepositoryRegistry', [new Reference('goat.domain.repository.registry')])
            ;
        }
    }
}
