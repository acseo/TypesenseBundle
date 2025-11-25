<?php

declare(strict_types=1);

namespace ACSEO\Bundle\TypesenseBundle\Tests\Unit\Client\Wrapper;

use ACSEO\TypesenseBundle\Client\Wrapper\DocumentsWrapper;
use ACSEO\TypesenseBundle\Logger\TypesenseLogger;
use PHPUnit\Framework\TestCase;
use Typesense\Documents;

class DocumentsWrapperTest extends TestCase
{
    public function testSearchLogsQuery(): void
    {
        $documents = $this->createMock(Documents::class);
        $logger = new TypesenseLogger();

        $documents->expects($this->once())
            ->method('search')
            ->willReturn(['found' => 10]);

        $wrapper = new DocumentsWrapper($documents, 'test_collection', $logger);
        $wrapper->search(['q' => 'test']);

        self::assertCount(1, $logger->getQueries());
        self::assertEquals('search', $logger->getQueries()[0]['operation']);
        self::assertEquals('test_collection', $logger->getQueries()[0]['collection']);
    }

    public function testSearchWithoutLogger(): void
    {
        $documents = $this->createMock(Documents::class);

        $documents->expects($this->once())
            ->method('search')
            ->willReturn(['found' => 0]);

        $wrapper = new DocumentsWrapper($documents, 'test', null);
        $result = $wrapper->search([]);

        self::assertEquals(['found' => 0], $result);
    }
}
