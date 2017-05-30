<?php

namespace Misteio\Neo4jBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('misteio_neo4j');

        $rootNode
            ->children()
                ->arrayNode('connections')
                ->isRequired()
                ->cannotBeEmpty()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('host')->end()
                            ->scalarNode('port')->end()
                            ->scalarNode('user')->end()
                            ->scalarNode('password')->end()
                            ->scalarNode('protocol')->end()
                            ->end()
                        ->end()
                    ->end()
                ->arrayNode('mappings')
                ->isRequired()
                ->cannotBeEmpty()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('class')->end()
                            ->scalarNode('transformer')->end()
                            ->booleanNode('auto_event')->end()
                            ->scalarNode('connection')->end()
                            ->arrayNode('indexes')
                                ->prototype('scalar')->end()->end()
                            ->arrayNode('composite_indexes')
                                ->prototype('scalar')->end()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
        ;

        return $treeBuilder;
    }
}
