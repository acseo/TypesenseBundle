<?php

declare(strict_types=1);

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
                    ->info('Typesense server information')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('url')->isRequired()->cannotBeEmpty()->end()
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
                                            ->scalarNode('entity_attribute')->end()
                                            ->scalarNode('name')->end()
                                            ->scalarNode('type')->end()
                                            ->booleanNode('facet')->end()
                                            ->booleanNode('optional')->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->scalarNode('default_sorting_field')->isRequired()->cannotBeEmpty()->end()
                                ->arrayNode('finders')
                                    ->info('Entity specific finders declaration')
                                    ->useAttributeAsKey('name')
                                    ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('finder_service')->end()
                                        ->arrayNode('finder_parameters')
                                            ->scalarPrototype()->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
