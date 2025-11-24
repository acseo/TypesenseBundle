<?php

declare(strict_types=1);

namespace ACSEO\Bundle\TypesenseBundle\Tests\Unit\DataCollector;

use ACSEO\TypesenseBundle\DataCollector\TypesenseDataCollector;
use ACSEO\TypesenseBundle\Logger\TypesenseLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TypesenseDataCollectorTest extends TestCase
{
    private TypesenseLogger $logger;
    private TypesenseDataCollector $collector;

    protected function setUp(): void
    {
        $this->logger = new TypesenseLogger();
        $this->collector = new TypesenseDataCollector($this->logger);
    }

    public function testGetName(): void
    {
        self::assertEquals('typesense', $this->collector->getName());
    }

    public function testCollectWithQueries(): void
    {
        $this->logger->logQuery('movies', 'search', ['q' => 'test'], 0.1, null, ['found' => 5]);
        $this->logger->logQuery('movies', 'search', ['q' => 'test2'], 0.2);

        $this->collector->collect(new Request(), new Response());

        self::assertEquals(2, $this->collector->getQueryCount());
        self::assertEquals(0.3, $this->collector->getTotalTime());
        self::assertCount(2, $this->collector->getQueries());
    }

    public function testCollectWithFailedQueries(): void
    {
        $this->logger->logQuery('movies', 'search', [], 0.1, 'Connection timeout');

        $this->collector->collect(new Request(), new Response());

        self::assertEquals(1, $this->collector->getFailedQueries());
    }

    public function testReset(): void
    {
        $this->logger->logQuery('movies', 'search', [], 0.1);
        $this->collector->collect(new Request(), new Response());

        self::assertEquals(1, $this->collector->getQueryCount());

        $this->collector->reset();

        self::assertEquals(0, $this->collector->getQueryCount());
    }
}
