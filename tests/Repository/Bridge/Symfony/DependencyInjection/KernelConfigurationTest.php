<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Bridge\Symfony\Tests\DependencyInjection;

use Goat\Domain\Repository\Bridge\Symfony\GoatRepositoryBundle;
use Goat\Domain\Repository\Bridge\Symfony\DependencyInjection\GoatRepositoryExtension;
use Goat\Domain\Repository\Registry\RepositoryRegistry;
use Goat\Runner\Runner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

final class KernelConfigurationTest extends TestCase
{
    private function getContainer()
    {
        // Code inspired by the SncRedisBundle, all credits to its authors.
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.debug'=> false,
            'kernel.bundles' => [
                GoatRepositoryBundle::class => ['all' => true],
            ],
            'kernel.cache_dir' => \sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => \dirname(__DIR__),
        ]));

        // OK, we will need this.
        $runnerDefinition = new Definition();
        $runnerDefinition->setClass(Runner::class);
        $runnerDefinition->setSynthetic(true);
        $container->setDefinition('goat.runner.default', $runnerDefinition);
        $container->setAlias(Runner::class, 'goat.runner.default');

        return $container;
    }

    private function getMinimalConfig(): array
    {
        return [];
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testTaggedServicesConfigLoad()
    {
        $extension = new GoatRepositoryExtension();
        $config = $this->getMinimalConfig();
        $extension->load([$config], $container = $this->getContainer());

        // Ensure event store configuration.
        self::assertTrue($container->hasAlias(RepositoryRegistry::class));
        self::assertTrue($container->hasAlias('goat.domain.repository.registry'));

        $container->compile();
    }
}
