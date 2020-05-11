<?php

namespace ACSEO\TypesenseBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use ACSEO\TypesenseBundle\Client\CollectionManager;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class ACSEOTypesenseExtension extends Extension
{

    /**
     * An array of collections as configured by the extension.
     *
     * @var array
     */
    private $collectionsConfig = [];

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
        $this->loadTransformer($container);
    }

    /**
     * Loads the configured clients.
     *
     * @param array            $clients   An array of clients configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @return array
     */
    private function loadClient($config, ContainerBuilder $container)
    {
        $clientId = ('typesense.client');

        $clientDef = new ChildDefinition('typesense.client_prototype');
        $clientDef->replaceArgument(0, $config['host']);
        $clientDef->replaceArgument(1, $config['key']);
        $container->setDefinition($clientId, $clientDef);
    }

    /**
     * Loads the configured collection.
     *
     * @param array            $collections   An array of collection configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    private function loadCollections(array $collections, ContainerBuilder $container)
    {
        foreach ($collections as $name => $config) {
            $collectionName = isset($config['collection_name']) ? $config['collection_name'] : $name;

            $primaryKeyExists = false;
            
            foreach ($config['fields'] as $fieldConfig) {
                if ($fieldConfig['type'] == 'primary') {
                    $primaryKeyExists = true;
                    break;
                }
            }
            if (!$primaryKeyExists) {
                $config['fields']['id'] = [
                    'name' => 'entity_id',
                    'type' => 'primary'
                ];
            }

            $this->collectionsConfig[$name] = [
                'typesense_name' => $collectionName,
                'entity' => $config['entity'],
                'name' => $name,
                'fields' => $config['fields'],
                'default_sorting_field' => $config['default_sorting_field']
            ];
        }
    }

    /**
     * Loads the collection manager.
     *
     * @param ContainerBuilder $container
     **/
    private function loadCollectionManager(ContainerBuilder $container)
    {
        $managerDef = $container->getDefinition('typesense.collection_manager');
        $managerDef->replaceArgument(2, $this->collectionsConfig);
    }

    /**
     * Loads the transformer
     *
     * @param ContainerBuilder $container
     **/
    private function loadTransformer(ContainerBuilder $container)
    {
        $managerDef = $container->getDefinition('typesense.transformer.doctrine_to_typesense');
        $managerDef->replaceArgument(0, $this->collectionsConfig);
    }
    
    /**
     * Loads the configured index finders.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $name      The index name
     * @param Reference                                               $index     Reference to the related index
     *
     * @return string
     */
    private function loadCollectionsFinder(ContainerBuilder $container)
    {
        foreach ($this->collectionsConfig as $name => $config) {
            $collectionName = $config['typesense_name'];

            $finderId = sprintf('typesense.finder.%s', $collectionName);
            $finderDef = new ChildDefinition('typesense.finder');
            $finderDef->replaceArgument(2, $config);
        
            $container->setDefinition($finderId, $finderDef);
        }
    }
}
