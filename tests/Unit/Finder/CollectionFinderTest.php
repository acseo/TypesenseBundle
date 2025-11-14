<?php

declare(strict_types=1);

namespace ACSEO\Bundle\TypesenseBundle\Tests\Unit\Finder;

use ACSEO\TypesenseBundle\Client\CollectionClient;
use ACSEO\TypesenseBundle\Finder\CollectionFinder;
use ACSEO\TypesenseBundle\Finder\TypesenseQuery;
use ACSEO\TypesenseBundle\Finder\TypesenseResponse;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use PHPUnit\Framework\TestCase;

class CollectionFinderTest extends TestCase
{
    private $collectionClient;
    private $entityManager;
    private $collectionConfig;
    private $collectionFinder;

    protected function setUp(): void
    {
        $this->collectionClient = $this->createMock(CollectionClient::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->collectionConfig = [
            'typesense_name' => 'test_collection',
            'entity' => 'App\Entity\TestEntity',
            'fields' => [
                'id' => [
                    'type' => 'primary',
                    'name' => 'id',
                    'entity_attribute' => 'id'
                ],
                'name' => [
                    'type' => 'string',
                    'name' => 'name',
                    'entity_attribute' => 'name'
                ]
            ]
        ];

        $this->collectionFinder = new CollectionFinder(
            $this->collectionClient,
            $this->entityManager,
            $this->collectionConfig
        );
    }

    public function testRawQuery()
    {
        $query = new TypesenseQuery('test search', 'name');
        $expectedResult = ['hits' => [], 'found' => 0];

        $this->collectionClient
            ->expects($this->once())
            ->method('search')
            ->with('test_collection', $query)
            ->willReturn($expectedResult);

        $result = $this->collectionFinder->rawQuery($query);

        $this->assertInstanceOf(TypesenseResponse::class, $result);
    }

    public function testQueryWithoutHydration()
    {
        $query = new TypesenseQuery('test search', 'name');
        $searchResult = [
            'hits' => [],
            'found' => 0,
            'page' => 1,
            'search_time_ms' => 5
        ];

        $this->collectionClient
            ->expects($this->once())
            ->method('search')
            ->with('test_collection', $query)
            ->willReturn($searchResult);

        $result = $this->collectionFinder->query($query);

        $this->assertInstanceOf(TypesenseResponse::class, $result);
        $this->assertEquals(0, $result->getFound());
    }

    public function testQueryWithHydration()
    {
        $query = new TypesenseQuery('test search', 'name');
        $searchResult = [
            'hits' => [
                ['document' => ['id' => 1, 'name' => 'Test 1']],
                ['document' => ['id' => 2, 'name' => 'Test 2']]
            ],
            'found' => 2,
            'page' => 1,
            'search_time_ms' => 10
        ];

        $this->collectionClient
            ->expects($this->once())
            ->method('search')
            ->with('test_collection', $query)
            ->willReturn($searchResult);

        $mockEntity1 = $this->createMockEntity(1, 'Test Entity 1');
        $mockEntity2 = $this->createMockEntity(2, 'Test Entity 2');

        $mockQuery = $this->createMock(Query::class);
        $mockQuery
            ->expects($this->once())
            ->method('setParameter')
            ->with('ids', [1, 2])
            ->willReturnSelf();
        $mockQuery
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([$mockEntity1, $mockEntity2]);

        $this->entityManager
            ->expects($this->once())
            ->method('createQuery')
            ->with('SELECT e FROM App\Entity\TestEntity e WHERE e.id IN (:ids)')
            ->willReturn($mockQuery);

        $result = $this->collectionFinder->query($query);

        $this->assertInstanceOf(TypesenseResponse::class, $result);
        $this->assertEquals(2, $result->getFound());
        $this->assertEquals([$mockEntity1, $mockEntity2], $result->getResults());
    }

    public function testHydrateResponse()
    {
        $response = new TypesenseResponse([
            'hits' => [
                ['document' => ['id' => 1, 'name' => 'Test 1']]
            ],
            'found' => 1
        ]);

        $mockEntity = $this->createMockEntity(1, 'Test Entity 1');

        $mockQuery = $this->createMock(Query::class);
        $mockQuery
            ->expects($this->once())
            ->method('setParameter')
            ->with('ids', [1])
            ->willReturnSelf();
        $mockQuery
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([$mockEntity]);

        $this->entityManager
            ->expects($this->once())
            ->method('createQuery')
            ->with('SELECT e FROM App\Entity\TestEntity e WHERE e.id IN (:ids)')
            ->willReturn($mockQuery);

        $result = $this->collectionFinder->hydrateResponse($response);

        $this->assertInstanceOf(TypesenseResponse::class, $result);
        $this->assertEquals([$mockEntity], $result->getResults());
    }

    public function testGetPrimaryKeyInfoThrowsExceptionWhenNoPrimaryKeyFound()
    {
        $configWithoutPrimary = [
            'typesense_name' => 'test_collection',
            'entity' => 'App\Entity\TestEntity',
            'fields' => [
                'name' => [
                    'type' => 'string',
                    'name' => 'name',
                    'entity_attribute' => 'name'
                ]
            ]
        ];

        $finder = new CollectionFinder(
            $this->collectionClient,
            $this->entityManager,
            $configWithoutPrimary
        );

        $query = new TypesenseQuery('test search', 'name');
        $searchResult = [
            'hits' => [['document' => ['name' => 'Test']]],
            'found' => 1
        ];

        $this->collectionClient
            ->expects($this->once())
            ->method('search')
            ->willReturn($searchResult);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Primary key info have not been found for Typesense collection test_collection');

        $finder->query($query);
    }

    public function testHydrationWithOrderedResults()
    {
        $query = new TypesenseQuery('test search', 'name');
        $searchResult = [
            'hits' => [
                ['document' => ['id' => 3, 'name' => 'Test 3']],
                ['document' => ['id' => 1, 'name' => 'Test 1']],
                ['document' => ['id' => 2, 'name' => 'Test 2']]
            ],
            'found' => 3
        ];

        $this->collectionClient
            ->expects($this->once())
            ->method('search')
            ->willReturn($searchResult);

        $mockEntity1 = $this->createMockEntity(1, 'Test Entity 1');
        $mockEntity2 = $this->createMockEntity(2, 'Test Entity 2');
        $mockEntity3 = $this->createMockEntity(3, 'Test Entity 3');

        $mockQuery = $this->createMock(Query::class);
        $mockQuery
            ->expects($this->once())
            ->method('setParameter')
            ->with('ids', [3, 1, 2])
            ->willReturnSelf();
        $mockQuery
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([$mockEntity1, $mockEntity2, $mockEntity3]);

        $this->entityManager
            ->expects($this->once())
            ->method('createQuery')
            ->willReturn($mockQuery);

        $result = $this->collectionFinder->query($query);

        $this->assertEquals([$mockEntity3, $mockEntity1, $mockEntity2], $result->getResults());
    }

    private function createMockEntity(int $id, string $name)
    {
        $mock = $this->getMockBuilder('stdClass')
            ->addMethods(['getId'])
            ->getMock();
        
        $mock->method('getId')
            ->willReturn($id);

        return $mock;
    }
}