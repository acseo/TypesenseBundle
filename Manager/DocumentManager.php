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
        return $this->client->delete(sprintf('collections/%s/documents/%d', $collection, $id));
    }
    
    public function index($collection, $data)
    {
        return $this->client->post(sprintf('collections/%s/documents', $collection), $data);
    }
}