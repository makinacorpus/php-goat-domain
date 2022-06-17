<?php

declare(strict_types=1);

namespace Goat\Domain\Repository\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class GoatRepositoryConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('goat_repository');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                /*
                ->arrayNode('monolog')
                    ->children()
                        ->booleanNode('log_pid')->defaultTrue()->end()
                        ->booleanNode('always_log_stacktrace')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('dispatcher')
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->booleanNode('with_logging')->defaultTrue()->end()
                        ->booleanNode('with_lock')->defaultFalse()->end()
                        ->booleanNode('with_event_store')->defaultFalse()->end()
                        ->booleanNode('with_profiling')->defaultTrue()->end()
                        ->booleanNode('with_retry')->defaultTrue()->end()
                        ->booleanNode('with_transaction')->defaultTrue()->end()
                    ->end()
                */
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
