<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Manager;

use ACSEO\TypesenseBundle\Client\CollectionClient;
use ACSEO\TypesenseBundle\Transformer\AbstractTransformer;

class CollectionManager
{
    private $collectionDefinitions;
    private $collectionClient;
    private $transformer;

    public function __construct(CollectionClient $collectionClient, AbstractTransformer $transformer, array $collectionDefinitions)
    {
        $this->collectionDefinitions = $collectionDefinitions;
        $this->collectionClient      = $collectionClient;
        $this->transformer           = $transformer;
    }

    public function getCollectionDefinitions()
    {
        return $this->collectionDefinitions;
    }

    public function getManagedClassNames()
    {
        $managedClassNames = [];
        foreach ($this->collectionDefinitions as $name => $collectionDefinition) {
            $collectionName = $collectionDefinition['typesense_name'] ?? $name;
            $managedClassNames[$collectionName] = $collectionDefinition['entity'];
        }

        return $managedClassNames;
    }

    public function getAllCollections()
    {
        return $this->collectionClient->list();
    }

    public function createAllCollections()
    {
        foreach ($this->collectionDefinitions as $name => $collectionDefinition) {
            $this->createCollection($name);
        }
    }

    public function deleteCollection($collectionDefinitionName)
    {
        $definition = $this->collectionDefinitions[$collectionDefinitionName];
        $this->collectionClient->delete($definition['typesense_name']);
    }

    public function deleteCollextion($collectionDefinitionName)
    {
        return $this->deleteCollection($collectionDefinitionName);
    }

    public function createCollection($collectionDefinitionName)
    {
        $definition       = $this->collectionDefinitions[$collectionDefinitionName];
        $fieldDefinitions = $definition['fields'];
        $fields           = [];
        foreach ($fieldDefinitions as $key => $fieldDefinition) {
            $fieldDefinition['type'] = $this->transformer->castType($fieldDefinition['type']);
            $fields[]                = $fieldDefinition;
        }

        //to pass the tests
        $tokenSeparators = array_key_exists('token_separators', $definition) ? $definition['token_separators'] : [];
        $symbolsToIndex  = array_key_exists('symbols_to_index', $definition) ? $definition['symbols_to_index'] : [];

        $this->collectionClient->create(
            $definition['typesense_name'],
            $fields,
            $definition['default_sorting_field'],
            $tokenSeparators,
            $symbolsToIndex
        );
    }
}
