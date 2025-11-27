<?php

declare(strict_types=1);

namespace ACSEO\Bundle\TypesenseBundle\Tests\Unit\Client\Wrapper;

use ACSEO\TypesenseBundle\Client\Wrapper\MultiSearchWrapper;
use ACSEO\TypesenseBundle\Logger\TypesenseLogger;
use PHPUnit\Framework\TestCase;
use Typesense\MultiSearch;

class MultiSearchWrapperTest extends TestCase
{
    public function testPerformLogsQuery(): void
    {
        $multiSearch = $this->createMock(MultiSearch::class);
        $logger = new TypesenseLogger();

        $multiSearch->expects($this->once())
            ->method('perform')
            ->willReturn(['results' => [['found' => 5]]]);

        $wrapper = new MultiSearchWrapper($multiSearch, $logger);
        $wrapper->perform(['searches' => [['collection' => 'test']]], []);

        self::assertCount(1, $logger->getQueries());
        self::assertEquals('multi_search', $logger->getQueries()[0]['operation']);
    }

    public function testPerformWithoutLogger(): void
    {
        $multiSearch = $this->createMock(MultiSearch::class);

        $multiSearch->expects($this->once())
            ->method('perform')
            ->willReturn(['results' => []]);

        $wrapper = new MultiSearchWrapper($multiSearch, null);
        $result = $wrapper->perform(['searches' => []], []);

        self::assertEquals(['results' => []], $result);
    }
}
