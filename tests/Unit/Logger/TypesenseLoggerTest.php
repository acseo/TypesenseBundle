<?php

declare(strict_types=1);

namespace ACSEO\Bundle\TypesenseBundle\Tests\Unit\Logger;

use ACSEO\TypesenseBundle\Logger\TypesenseLogger;
use PHPUnit\Framework\TestCase;

class TypesenseLoggerTest extends TestCase
{
    private TypesenseLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new TypesenseLogger();
    }

    public function testLogQuery(): void
    {
        $params = ['q' => 'test', 'query_by' => 'title'];
        $response = ['found' => 10];

        $this->logger->logQuery('movies', 'search', $params, 0.1, null, $response);

        $queries = $this->logger->getQueries();

        self::assertCount(1, $queries);
        self::assertEquals('movies', $queries[0]['collection']);
        self::assertEquals('search', $queries[0]['operation']);
        self::assertEquals(0.1, $queries[0]['duration']);
    }

    public function testLogQueryWithError(): void
    {
        $this->logger->logQuery('movies', 'search', [], 0.05, 'Connection failed');

        self::assertEquals(1, $this->logger->getFailedQueries());
    }

    public function testGetExecutionTime(): void
    {
        $this->logger->logQuery('movies', 'search', [], 0.1);
        $this->logger->logQuery('movies', 'search', [], 0.2);

        self::assertEquals(300.0, $this->logger->getExecutionTime());
    }

    public function testReset(): void
    {
        $this->logger->logQuery('movies', 'search', [], 0.1);

        self::assertCount(1, $this->logger->getQueries());

        $this->logger->reset();

        self::assertCount(0, $this->logger->getQueries());
    }

    public function testHttpMethodDetection(): void
    {
        $this->logger->logQuery('movies', 'search', ['vector_query' => 'test'], 0.1);

        $queries = $this->logger->getQueries();
        self::assertEquals('POST', $queries[0]['method']);
    }

    public function testSetBaseUrl(): void
    {
        $this->logger->setBaseUrl('http://localhost:8108');
        $this->logger->logQuery('movies', 'search', [], 0.1);

        $queries = $this->logger->getQueries();
        self::assertStringContainsString('http://localhost:8108', $queries[0]['url']);
    }
}
