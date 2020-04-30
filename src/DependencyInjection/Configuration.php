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
                    ->info('Typesense host information')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('key')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->arrayNode('collections')
                    ->info('Collection definition')
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
                                ->scalarNode('default_sorting_field')->isRequired()->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
