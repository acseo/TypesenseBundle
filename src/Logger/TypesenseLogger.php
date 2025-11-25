<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Logger;

use Webmozart\Assert\Assert;

/**
 * Logger for Typesense queries
 *
 * Inspired by FOSElasticaBundle's ElasticaLogger pattern
 */
class TypesenseLogger implements QueryLoggerInterface
{
    private array $queries = [];
    private int $nbQueries = 0;
    private ?string $baseUrl = null;

    public function setBaseUrl(?string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Log a Typesense operation (generic for all types of operations)
     */
    public function logQuery(
        ?string $collection,
        string $operation,
        array $params,
        float $duration,
        ?string $error = null,
        ?array $response = null,
        ?string $method = null,
        ?string $endpoint = null
    ): void {
        if ($method === null) {
            if (str_contains($operation, 'multi_search')) {
                $httpMethod = 'POST';
            } elseif (isset($params['vector_query'])) {
                $httpMethod = 'POST';
            } else {
                $httpMethod = 'GET';
            }
        } else {
            $httpMethod = $method;
        }

        $httpEndpoint = $endpoint ?? $this->buildEndpoint($collection, $operation);
        $fullUrl = $this->baseUrl ? rtrim($this->baseUrl, '/') . $httpEndpoint : $httpEndpoint;

        $queryData = [
            'operation' => $operation,
            'method' => $httpMethod,
            'endpoint' => $httpEndpoint,
            'url' => $fullUrl,
            'params' => $params,
            'duration' => $duration,
            'executionMS' => $duration * 1000, // Convert to milliseconds
            'error' => $error,
            'timestamp' => microtime(true),
        ];

        if ($collection !== null) {
            $queryData['collection'] = $collection;
        }

        if ($response !== null) {
            $queryData['response'] = $this->extractResponseSummary($response);
        }

        $this->queries[] = $queryData;
        $this->nbQueries++;
    }

    /**
     * Build endpoint URL from operation
     */
    private function buildEndpoint(?string $collection, string $operation): string
    {
        if (str_contains($operation, 'multi_search')) {
            return '/multi_search';
        }

        if ($operation === 'list_collections') {
            return '/collections';
        }

        if (str_contains($operation, 'create_collection')) {
            return '/collections';
        }

        if (str_contains($operation, 'delete_collection')) {
            return '/collections/' . ($collection ?? '{collection}');
        }

        if (str_contains($operation, 'import')) {
            return '/collections/' . ($collection ?? '{collection}') . '/documents/import';
        }

        if (str_contains($operation, 'export')) {
            return '/collections/' . ($collection ?? '{collection}') . '/documents/export';
        }

        return '/collections/' . ($collection ?? '{collection}') . '/documents/search';
    }

    /**
     * Extract summary from response
     */
    private function extractResponseSummary(array $response): array
    {
        return [
            'hits' => $response['found'] ?? $response['out_of'] ?? 0,
            'search_time_ms' => $response['search_time_ms'] ?? null,
            'page' => $response['page'] ?? 1,
            'facets_count' => isset($response['facet_counts']) ? count($response['facet_counts']) : 0,
        ];
    }

    /**
     * Get all logged queries
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Get number of queries executed
     */
    public function getNbQueries(): int
    {
        return $this->nbQueries;
    }

    /**
     * Get total execution time in milliseconds
     */
    public function getExecutionTime(): float
    {
        $time = 0;
        foreach ($this->queries as $query) {
            $time += $query['executionMS'];
        }

        return $time;
    }

    /**
     * Get number of failed queries
     */
    public function getFailedQueries(): int
    {
        return count(array_filter($this->queries, fn($q) => $q['error'] !== null));
    }

    /**
     * Reset the logger
     */
    public function reset(): void
    {
        $this->queries = [];
        $this->nbQueries = 0;
    }
}
