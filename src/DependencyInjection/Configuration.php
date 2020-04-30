<?php

namespace ACSEO\TypesenseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('acseo_typesense');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('typesense')
                    ->children()
                        ->scalarNode('host')->end()
                        ->scalarNode('key')->end()
                    ->end()
                ->end()
                ->arrayNode('collections')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('entity')->end()
                                ->arrayNode('fields')
                                    ->arrayPrototype()
                                        ->children()
                                            ->scalarNode('name')->end()
                                            ->scalarNode('type')->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->scalarNode('default_sorting_field')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
