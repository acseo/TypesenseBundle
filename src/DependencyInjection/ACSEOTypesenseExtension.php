<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class ACSEOTypesenseExtension extends Extension
{
    /**
     * An array of collections as configured by the extension.
     *
     * @var array
     */
    private $collectionsConfig = [];

    /**
     * An array of finder as configured by the extension.
     *
     * @var array
     */
    private $findersConfig = [];

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        if (empty($config['typesense']) || empty($config['collections'])) {
            // No Host or collection are defined
            return;
        }

        $loader = new XMlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.xml');

        $this->loadClient($config['typesense'], $container);

        $this->loadCollections($config['collections'], $container);

        $this->loadCollectionManager($container);
        $this->loadCollectionsFinder($container);

        $this->loadFinderServices($container);

        $this->loadTransformer($container);
        $this->configureController($container);
    }

    /**
     * Loads the configured clients.
     *
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    private function loadClient($config, ContainerBuilder $container)
    {
        $clientId = ('typesense.client');

        $clientDef = new ChildDefinition('typesense.client_prototype');
        $clientDef->replaceArgument(0, $config['url']);
        $clientDef->replaceArgument(1, $config['key']);
        $container->setDefinition($clientId, $clientDef);
    }

    /**
     * Loads the configured collection.
     *
     * @param array            $collections An array of collection configurations
     * @param ContainerBuilder $container   A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException
     */
    private function loadCollections(array $collections, ContainerBuilder $container)
    {
        foreach ($collections as $name => $config) {
            $collectionName = $config['collection_name'] ?? $name;

            $primaryKeyExists = false;

            foreach ($config['fields'] as $key => $fieldConfig) {
                if (!isset($fieldConfig['name'])) {
                    throw new \Exception('acseo_typesense.collections.'.$name.'.'.$key.'.name must be set');
                }
                if (!isset($fieldConfig['type'])) {
                    throw new \Exception('acseo_typesense.collections.'.$name.'.'.$key.'.type must be set');
                }

                if ($fieldConfig['type'] === 'primary') {
                    $primaryKeyExists = true;
                }
                if (!isset($fieldConfig['entity_attribute'])) {
                    $config['fields'][$key]['entity_attribute'] = $key;
                }
            }

            if (!$primaryKeyExists) {
                $config['fields']['id'] = [
                    'name' => 'entity_id',
                    'type' => 'primary',
                ];
            }

            if (isset($config['finders'])) {
                foreach ($config['finders'] as $finderName => $finderConfig) {
                    $finderName                      = $collectionName.'.'.$finderName;
                    $finderConfig['collection_name'] = $collectionName;
                    $finderConfig['finder_name']     = $finderName;
                    if (!isset($finderConfig['finder_parameters']['query_by'])) {
                        throw new \Exception('acseo_typesense.collections.'.$finderName.'.finder_parameters.query_by must be set');
                    }
                    $this->findersConfig[$finderName] = $finderConfig;
                }
            }

            $this->collectionsConfig[$name] = [
                'typesense_name'        => $collectionName,
                'entity'                => $config['entity'],
                'name'                  => $name,
                'fields'                => $config['fields'],
                'default_sorting_field' => $config['default_sorting_field'],
                'token_separators'      => $config['token_separators'],
                'symbols_to_index'      => $config['symbols_to_index'],
            ];
        }
    }

    /**
     * Loads the collection manager.
     */
    private function loadCollectionManager(ContainerBuilder $container)
    {
        $managerDef = $container->getDefinition('typesense.collection_manager');
        $managerDef->replaceArgument(2, $this->collectionsConfig);
    }

    /**
     * Loads the transformer.
     */
    private function loadTransformer(ContainerBuilder $container)
    {
        $managerDef = $container->getDefinition('typesense.transformer.doctrine_to_typesense');
        $managerDef->replaceArgument(0, $this->collectionsConfig);
    }

    /**
     * Loads the configured index finders.
     */
    private function loadCollectionsFinder(ContainerBuilder $container)
    {
        foreach ($this->collectionsConfig as $name => $config) {
            $collectionName = $config['typesense_name'];

            $finderId  = sprintf('typesense.finder.%s', $collectionName);
            $finderDef = new ChildDefinition('typesense.finder');
            $finderDef->replaceArgument(2, $config);

            $container->setDefinition($finderId, $finderDef);
        }
    }

    /**
     * Loads the configured Finder services.
     */
    private function loadFinderServices(ContainerBuilder $container)
    {
        foreach ($this->findersConfig as $name => $config) {
            $finderName     = $config['finder_name'];
            $collectionName = $config['collection_name'];
            $finderId       = sprintf('typesense.finder.%s', $collectionName);

            if (isset($config['finder_service'])) {
                $finderId = $config['finder_service'];
            }

            $specifiFinderId  = sprintf('typesense.specificfinder.%s', $finderName);
            $specifiFinderDef = new ChildDefinition('typesense.specificfinder');
            $specifiFinderDef->replaceArgument(0, new Reference($finderId));
            $specifiFinderDef->replaceArgument(1, $config['finder_parameters']);

            $container->setDefinition($specifiFinderId, $specifiFinderDef);
        }
    }

    private function configureController(ContainerBuilder $container)
    {
        $finderServices = [];
        foreach ($this->findersConfig as $name => $config) {
            $finderName                  = $config['finder_name'];
            $finderId                    = sprintf('typesense.specificfinder.%s', $finderName);
            $finderServices[$finderName] = new Reference($finderId);
        }
        $controllerDef = $container->getDefinition('typesense.autocomplete_controller');
        $controllerDef->replaceArgument(0, $finderServices);
    }
}
