<?php

declare(strict_types=1);

use ACSEO\TypesenseBundle\Client\CollectionClient;
use ACSEO\TypesenseBundle\Client\TypesenseClient;
use ACSEO\TypesenseBundle\Command\CreateCommand;
use ACSEO\TypesenseBundle\Command\ImportCommand;
use ACSEO\TypesenseBundle\Controller\TypesenseAutocompleteController;
use ACSEO\TypesenseBundle\DataCollector\TypesenseDataCollector;
use ACSEO\TypesenseBundle\EventListener\TypesenseIndexer;
use ACSEO\TypesenseBundle\Finder\CollectionFinder;
use ACSEO\TypesenseBundle\Finder\SpecificCollectionFinder;
use ACSEO\TypesenseBundle\Logger\TypesenseLogger;
use ACSEO\TypesenseBundle\Manager\CollectionManager;
use ACSEO\TypesenseBundle\Manager\DocumentManager;
use ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->public();

    // Abstract definition for client
    $services->set('typesense.client_prototype', TypesenseClient::class)
        ->abstract()
        ->args([
            '', // host
            '', // key
            service('typesense.logger')->nullOnInvalid(), // logger
        ]);

    // Abstract definition for all generic finders
    $services->set('typesense.finder', CollectionFinder::class)
        ->abstract()
        ->args([
            service('typesense.collection_client'),
            service('doctrine.orm.entity_manager'),
            '', // collection config
        ]);

    // Abstract definition for all specific finders
    $services->set('typesense.specificfinder', SpecificCollectionFinder::class)
        ->abstract()
        ->args([
            '', // generic finder class
            '', // finder arguments
        ]);

    // Autocomplete controller
    $services->set('typesense.autocomplete_controller', TypesenseAutocompleteController::class)
        ->args([
            '', // routes
        ]);

    // Logger
    $services->set('typesense.logger', TypesenseLogger::class)
        ->public(false);

    $services->alias(TypesenseLogger::class, 'typesense.logger');

    // Data Collector
    $services->set('typesense.data_collector', TypesenseDataCollector::class)
        ->public(false)
        ->args([
            service('typesense.logger'),
        ])
        ->tag('data_collector', [
            'template' => '@ACSEOTypesense/DataCollector/typesense.html.twig',
            'id' => 'typesense',
            'priority' => 300,
        ]);

    // Collection Client
    $services->set('typesense.collection_client', CollectionClient::class)
        ->args([
            service('typesense.client'),
            service('typesense.logger')->nullOnInvalid(),
        ]);

    $services->alias(CollectionClient::class, 'typesense.collection_client');
    $services->alias(TypesenseClient::class, 'typesense.client');

    // Collection Manager
    $services->set('typesense.collection_manager', CollectionManager::class)
        ->args([
            service('typesense.collection_client'),
            service('typesense.transformer.doctrine_to_typesense'),
            '', // collections
        ]);

    $services->alias(CollectionManager::class, 'typesense.collection_manager');

    // Document Manager
    $services->set('typesense.document_manager', DocumentManager::class)
        ->args([
            service('typesense.client'),
        ]);

    // Doctrine Event Listener
    $services->set('typesense.listener.doctrine_indexer', TypesenseIndexer::class)
        ->args([
            service('typesense.collection_manager'),
            service('typesense.document_manager'),
            service('typesense.transformer.doctrine_to_typesense'),
        ])
        ->tag('doctrine.event_listener', ['event' => 'postPersist', 'priority' => 500, 'connection' => 'default'])
        ->tag('doctrine.event_listener', ['event' => 'postUpdate', 'priority' => 500, 'connection' => 'default'])
        ->tag('doctrine.event_listener', ['event' => 'preRemove', 'priority' => 500, 'connection' => 'default'])
        ->tag('doctrine.event_listener', ['event' => 'postRemove', 'priority' => 500, 'connection' => 'default'])
        ->tag('doctrine.event_listener', ['event' => 'postFlush', 'priority' => 500, 'connection' => 'default']);

    // Transformer
    $services->set('typesense.transformer.doctrine_to_typesense', DoctrineToTypesenseTransformer::class)
        ->args([
            '', // collections
            service('property_accessor'),
            service('service_container'),
        ]);

    // Commands
    $services->set('typesense.command.create', CreateCommand::class)
        ->tag('console.command')
        ->args([
            service('typesense.collection_manager'),
        ]);

    $services->set('typesense.command.import', ImportCommand::class)
        ->tag('console.command')
        ->args([
            service('doctrine.orm.entity_manager'),
            service('typesense.collection_manager'),
            service('typesense.document_manager'),
            service('typesense.transformer.doctrine_to_typesense'),
        ]);
};
