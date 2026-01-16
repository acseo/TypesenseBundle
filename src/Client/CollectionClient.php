<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Client;

use ACSEO\TypesenseBundle\Logger\QueryLoggerInterface;
use ACSEO\TypesenseBundle\Finder\TypesenseQuery;

class CollectionClient
{
    private $client;
    private $logger;

    public function __construct(TypesenseClient $client, ?QueryLoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->logger = $logger;

        if ($this->logger && $this->client->getBaseUrl()) {
            $this->logger->setBaseUrl($this->client->getBaseUrl());
        }
    }

    public function search(string $collectionName, TypesenseQuery $query)
    {
        if (!$this->client->isOperationnal()) {
            return null;
        }

        $startTime = microtime(true);
        $error = null;

        try {
            $result = $this->client->collections[$collectionName]->documents->search($query->getParameters());

            if ($this->logger) {
                $duration = microtime(true) - $startTime;
                $this->logger->logQuery($collectionName, 'search', $query->getParameters(), $duration, null, $result);
            }

            return $result;
        } catch (\Exception $e) {
            $error = $e->getMessage();

            if ($this->logger) {
                $duration = microtime(true) - $startTime;
                $this->logger->logQuery($collectionName, 'search', $query->getParameters(), $duration, $error, null);
            }

            throw $e;
        }
    }

    public function multiSearch(array $searchRequests, ?TypesenseQuery $commonSearchParams = null)
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

        $startTime = microtime(true);
        $error = null;

        try {
            $result = $this->client->multiSearch->perform(
                ['searches' => $searches],
                $commonSearchParams ? $commonSearchParams->getParameters() : []
            );

            if ($this->logger) {
                $duration = microtime(true) - $startTime;
                $this->logger->logQuery(null, 'multi_search', ['searches' => $searches], $duration, null, $result);
            }

            return $result;
        } catch (\Exception $e) {
            $error = $e->getMessage();

            if ($this->logger) {
                $duration = microtime(true) - $startTime;
                $this->logger->logQuery(null, 'multi_search', ['searches' => $searches], $duration, $error, null);
            }

            throw $e;
        }
    }

    public function list()
    {
        if (!$this->client->isOperationnal()) {
            return null;
        }

        return $this->client->collections->retrieve();
    }

    public function create($name, $fields, $defaultSortingField, array $tokenSeparators, array $symbolsToIndex, bool $enableNestedFields = false)
    {
        if (!$this->client->isOperationnal()) {
            return null;
        }

        $options = [
            'name'                  => $name,
            'fields'                => $fields,
            'default_sorting_field' => $defaultSortingField,
            'token_separators'      => $tokenSeparators,
            'symbols_to_index'      => $symbolsToIndex,
            'enable_nested_fields'  => $enableNestedFields,
        ];

        $this->client->collections->create($options);
    }

    public function delete(string $name)
    {
        if (!$this->client->isOperationnal()) {
            return null;
        }

        return $this->client->collections[$name]->delete();
    }
}
