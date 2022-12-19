<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Client;

use ACSEO\TypesenseBundle\Finder\TypesenseQuery;

class CollectionClient
{
    private $client;

    public function __construct(TypesenseClient $client)
    {
        $this->client = $client;
    }

    public function search(string $collectionName, TypesenseQuery $query)
    {
        if (!$this->client->isOperationnal()) {
            return null;
        }

        return $this->client->collections[$collectionName]->documents->search($query->getParameters());
    }

    public function multiSearch(array $searchRequests, ?TypesenseQuery $commonSearchParams)
    {
        if (!$this->client->isOperationnal()) {
            return null;
        }

        $searches = [];
        foreach ($searchRequests as $sr) {
            if (!$sr instanceof TypesenseQuery) {
                throw new \Exception('searchRequests must be an array  of TypesenseQuery objects');
            }
            if (!$sr->hasParameter('collection')) {
                throw new \Exception('TypesenseQuery must have the key : `collection` in order to perform multiSearch');
            }
            $searches[] = $sr->getParameters();
        }

        return $this->client->multiSearch->perform(
            [
                'searches' => $searches,
            ],
            $commonSearchParams ? $commonSearchParams->getParameters() : []
        );
    }

    public function list()
    {
        if (!$this->client->isOperationnal()) {
            return null;
        }

        return $this->client->collections->retrieve();
    }

    public function create($name, $fields, $defaultSortingField, array $tokenSeparators, array $symbolsToIndex)
    {
        if (!$this->client->isOperationnal()) {
            return null;
        }

        $this->client->collections->create([
            'name'                  => $name,
            'fields'                => $fields,
            'default_sorting_field' => $defaultSortingField,
            'token_separators'      => $tokenSeparators,
            'symbols_to_index'      => $symbolsToIndex,
        ]);
    }

    public function delete(string $name)
    {
        if (!$this->client->isOperationnal()) {
            return null;
        }

        return $this->client->collections[$name]->delete();
    }
}
