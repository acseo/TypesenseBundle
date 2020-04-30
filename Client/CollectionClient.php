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

    public function get(string $endpoint): array
    {
        return $this->client->get(sprintf('collections/%s', $endpoint));
    }

    public function list()
    {
        return $this->client->get('collections');
    }

    public function create($name, $fields, $defaultSortingField)
    {
        $this->client->post("collections", [
            "name" => $name,
            "fields" => $fields,
            "default_sorting_field" => $defaultSortingField
        ]);
    }


    public function delete(string $name)
    {
        return $this->client->delete('collections/'.$name);
    }
}
