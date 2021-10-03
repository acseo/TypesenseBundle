<?php

namespace ACSEO\TypesenseBundle\Manager;

use ACSEO\TypesenseBundle\Client\TypesenseClient;

class DocumentManager
{
    private $client;
    
    public function __construct(TypesenseClient $client)
    {
        $this->client = $client;
    }

    public function delete($collection, $id)
    {
        return $this->client->collections[$collection]->documents[$id]->delete();
    }
    
    public function index($collection, $data)
    {
        return $this->client->collections[$collection]->documents->create($data);
    }

    public function import(string $collection, array $data, string $action = 'create')
    {
        return $this->client->collections[$collection]->documents->import($data, ['action' => $action]);
    }
}
