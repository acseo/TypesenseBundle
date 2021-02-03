<?php

namespace ACSEO\TypesenseBundle\Client;

class CollectionClient
{
    private $client;

    /**
     * @param array $indexes
     * @param Index $defaultIndex
     */
    public function __construct(TypesenseClient $client)
    {
        $this->client = $client;
    }

    public function get(string $collectionName, array $queryParameters)
    {
        return $this->client->collections[$collectionName]->documents->search($queryParameters);
    }

    public function list()
    {
        return $this->client->collections->retrieve();
    }

    public function create($name, $fields, $defaultSortingField)
    {
        $this->client->collections->create([
            'name' => $name,
            'fields' => $fields,
            'default_sorting_field' => $defaultSortingField
        ]);
    }
    
    public function delete(string $name)
    {
        return $this->client->collections[$name]->delete();
    }
}
