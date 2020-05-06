<?php

namespace ACSEO\TypesenseBundle\Manager;

use ACSEO\TypesenseBundle\Client\CollectionClient;

class CollectionManager
{
    private $collectionDefinitions;
    private $collectionClient;

    public function __construct(CollectionClient $collectionClient, array $collectionDefinitions)
    {
        $this->collectionDefinitions = $collectionDefinitions;
        $this->collectionClient = $collectionClient;
    }

    public function getCollectionDefinitions()
    {
        return $this->collectionDefinitions;
    }
    
    public function getManagedClassNames()
    {
        $managedClassNames = [];
        foreach ($this->collectionDefinitions as $name => $collectionDefinition) {
            $managedClassNames[$name] = $collectionDefinition['entity'];
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

    public function deleteCollextion($collectionDefinitionName)
    {
        $definition = $this->collectionDefinitions[$collectionDefinitionName];
        $this->collectionClient->delete($definition['typesense_name']);
    }

    public function createCollection($collectionDefinitionName)
    {
        $definition = $this->collectionDefinitions[$collectionDefinitionName];
        $fieldDefinitions = $definition['fields'];
        $fields = [];
        foreach ($fieldDefinitions as $key => $fieldDefinition) {
            $fieldDefinition['type'] = $this->castType($fieldDefinition['type']);
            $fields[] = $fieldDefinition;
        }

        $this->collectionClient->create(
            $definition['typesense_name'],
            $fields,
            $definition['default_sorting_field']
        );
    }

    private function castType($type)
    {
        if ($type == 'datetime') {
            return 'int32';
        }
        if ($type == 'primary') {
            return 'int32';
        }
        if ($type == 'object') {
            return 'string';
        }

        return $type;
    }
}
