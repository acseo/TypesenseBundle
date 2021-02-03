<?php

namespace ACSEO\TypesenseBundle\Manager;

use ACSEO\TypesenseBundle\Client\TypesenseClient;
use Typesense\Client;

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
}
