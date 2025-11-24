<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\DataCollector;

use ACSEO\TypesenseBundle\Logger\TypesenseLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;

/**
 * Data collector for Typesense queries
 *
 * Inspired by FOSElasticaBundle's ElasticaDataCollector pattern
 */
class TypesenseDataCollector extends DataCollector
{
    protected TypesenseLogger $logger;

    public function __construct(TypesenseLogger $logger)
    {
        $this->logger = $logger;
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $queries = $this->logger->getQueries();
        $sanitizedQueries = [];
        foreach ($queries as $query) {
            $sanitizedQuery = [
                'operation' => (string) $query['operation'],
                'method' => $query['method'] ?? 'GET',
                'endpoint' => $query['endpoint'] ?? '/',
                'url' => $query['url'] ?? null,
                'params' => $this->sanitizeParams($query['params'] ?? []),
                'duration' => (float) $query['duration'],
                'executionMS' => (float) $query['executionMS'],
                'error' => $query['error'] ?? null,
                'timestamp' => (float) $query['timestamp'],
            ];

            if (isset($query['collection'])) {
                $sanitizedQuery['collection'] = (string) $query['collection'];
            }

            if (isset($query['response'])) {
                $sanitizedQuery['response'] = $query['response'];
            }

            $sanitizedQueries[] = $sanitizedQuery;
        }

        $this->data = [
            'queries' => $sanitizedQueries,
            'query_count' => $this->logger->getNbQueries(),
            'total_time' => $this->logger->getExecutionTime() / 1000, // Convert back to seconds for display
            'failed_queries' => $this->logger->getFailedQueries(),
        ];
    }

    private function sanitizeParams(array $params): array
    {
        return json_decode(json_encode($params), true) ?? [];
    }

    public function reset(): void
    {
        $this->logger->reset();
        $this->data = [];
    }

    public function getQueries(): array
    {
        return $this->data['queries'] ?? [];
    }

    public function getQueryCount(): int
    {
        return $this->data['query_count'] ?? 0;
    }

    public function getTotalTime(): float
    {
        return $this->data['total_time'] ?? 0;
    }

    public function getFailedQueries(): int
    {
        return $this->data['failed_queries'] ?? 0;
    }

    public function getName(): string
    {
        return 'typesense';
    }
}
