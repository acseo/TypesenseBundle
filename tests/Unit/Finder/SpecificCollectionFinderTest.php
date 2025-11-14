<?php

declare(strict_types=1);

namespace ACSEO\Bundle\TypesenseBundle\Tests\Unit\Finder;

use ACSEO\TypesenseBundle\Finder\CollectionFinderInterface;
use ACSEO\TypesenseBundle\Finder\SpecificCollectionFinder;
use ACSEO\TypesenseBundle\Finder\TypesenseQuery;
use ACSEO\TypesenseBundle\Finder\TypesenseResponse;
use PHPUnit\Framework\TestCase;

class SpecificCollectionFinderTest extends TestCase
{
    private $collectionFinder;
    private $specificCollectionFinder;

    protected function setUp(): void
    {
        $this->collectionFinder = $this->createMock(CollectionFinderInterface::class);
    }

    public function testSearch()
    {
        $arguments = [
            'query_by' => 'name,description',
            'filter_by' => 'category:=books',
            'sort_by' => 'created_at:desc'
        ];

        $this->specificCollectionFinder = new SpecificCollectionFinder(
            $this->collectionFinder,
            $arguments
        );

        $expectedResponse = new TypesenseResponse(['hits' => [], 'found' => 0]);

        $this->collectionFinder
            ->expects($this->once())
            ->method('query')
            ->with($this->callback(function (TypesenseQuery $query) {
                $parameters = $query->getParameters();
                return $parameters['q'] === 'test search' &&
                       $parameters['query_by'] === 'name,description' &&
                       $parameters['filter_by'] === 'category:=books' &&
                       $parameters['sort_by'] === 'created_at:desc';
            }))
            ->willReturn($expectedResponse);

        $result = $this->specificCollectionFinder->search('test search');

        $this->assertSame($expectedResponse, $result);
    }

    public function testSearchWithMinimalArguments()
    {
        $arguments = [
            'query_by' => 'title'
        ];

        $this->specificCollectionFinder = new SpecificCollectionFinder(
            $this->collectionFinder,
            $arguments
        );

        $expectedResponse = new TypesenseResponse(['hits' => [], 'found' => 0]);

        $this->collectionFinder
            ->expects($this->once())
            ->method('query')
            ->with($this->callback(function (TypesenseQuery $query) {
                $parameters = $query->getParameters();
                return $parameters['q'] === 'search term' &&
                       $parameters['query_by'] === 'title' &&
                       count($parameters) === 2;
            }))
            ->willReturn($expectedResponse);

        $result = $this->specificCollectionFinder->search('search term');

        $this->assertSame($expectedResponse, $result);
    }

    public function testSearchWithMultipleParameters()
    {
        $arguments = [
            'query_by' => 'name,content',
            'filter_by' => 'status:=published',
            'sort_by' => 'priority:desc',
            'per_page' => 10,
            'facet_by' => 'category',
            'max_facet_values' => 5
        ];

        $this->specificCollectionFinder = new SpecificCollectionFinder(
            $this->collectionFinder,
            $arguments
        );

        $expectedResponse = new TypesenseResponse(['hits' => [], 'found' => 0]);

        $this->collectionFinder
            ->expects($this->once())
            ->method('query')
            ->with($this->callback(function (TypesenseQuery $query) {
                $parameters = $query->getParameters();
                return $parameters['q'] === 'complex search' &&
                       $parameters['query_by'] === 'name,content' &&
                       $parameters['filter_by'] === 'status:=published' &&
                       $parameters['sort_by'] === 'priority:desc' &&
                       $parameters['per_page'] === 10 &&
                       $parameters['facet_by'] === 'category' &&
                       $parameters['max_facet_values'] === 5;
            }))
            ->willReturn($expectedResponse);

        $result = $this->specificCollectionFinder->search('complex search');

        $this->assertSame($expectedResponse, $result);
    }

    public function testRawQuery()
    {
        $arguments = ['query_by' => 'name'];
        $this->specificCollectionFinder = new SpecificCollectionFinder(
            $this->collectionFinder,
            $arguments
        );

        $query = new TypesenseQuery('test', 'field');
        $expectedResult = ['hits' => [], 'found' => 0];

        $this->collectionFinder
            ->expects($this->once())
            ->method('rawQuery')
            ->with($query)
            ->willReturn($expectedResult);

        $result = $this->specificCollectionFinder->rawQuery($query);

        $this->assertSame($expectedResult, $result);
    }

    public function testQuery()
    {
        $arguments = ['query_by' => 'name'];
        $this->specificCollectionFinder = new SpecificCollectionFinder(
            $this->collectionFinder,
            $arguments
        );

        $query = new TypesenseQuery('test', 'field');
        $expectedResponse = new TypesenseResponse(['hits' => [], 'found' => 0]);

        $this->collectionFinder
            ->expects($this->once())
            ->method('query')
            ->with($query)
            ->willReturn($expectedResponse);

        $result = $this->specificCollectionFinder->query($query);

        $this->assertSame($expectedResponse, $result);
    }

    public function testHydrateResponse()
    {
        $arguments = ['query_by' => 'name'];
        $this->specificCollectionFinder = new SpecificCollectionFinder(
            $this->collectionFinder,
            $arguments
        );

        $response = new TypesenseResponse(['hits' => [], 'found' => 0]);
        $expectedResponse = new TypesenseResponse(['hits' => [], 'found' => 0]);

        $this->collectionFinder
            ->expects($this->once())
            ->method('hydrateResponse')
            ->with($response)
            ->willReturn($expectedResponse);

        $result = $this->specificCollectionFinder->hydrateResponse($response);

        $this->assertSame($expectedResponse, $result);
    }

    public function testSearchModifiesArgumentsOnFirstCall()
    {
        $arguments = [
            'query_by' => 'name',
            'filter_by' => 'status:=active',
            'sort_by' => 'date:desc'
        ];

        $this->specificCollectionFinder = new SpecificCollectionFinder(
            $this->collectionFinder,
            $arguments
        );

        $expectedResponse = new TypesenseResponse(['hits' => [], 'found' => 0]);

        $this->collectionFinder
            ->expects($this->once())
            ->method('query')
            ->with($this->callback(function (TypesenseQuery $query) {
                $parameters = $query->getParameters();
                return $parameters['q'] === 'first search' &&
                       $parameters['query_by'] === 'name' &&
                       $parameters['filter_by'] === 'status:=active' &&
                       $parameters['sort_by'] === 'date:desc';
            }))
            ->willReturn($expectedResponse);

        $result = $this->specificCollectionFinder->search('first search');

        $this->assertSame($expectedResponse, $result);
    }

    public function testSecondSearchCallWorksWithNullQueryBy()
    {
        $arguments = [
            'query_by' => 'name',
            'filter_by' => 'status:=active'
        ];

        $this->specificCollectionFinder = new SpecificCollectionFinder(
            $this->collectionFinder,
            $arguments
        );

        $expectedResponse = new TypesenseResponse(['hits' => [], 'found' => 0]);

        $this->collectionFinder
            ->expects($this->exactly(2))
            ->method('query')
            ->withConsecutive(
                [$this->callback(function (TypesenseQuery $query) {
                    $parameters = $query->getParameters();
                    return $parameters['q'] === 'first search' &&
                           $parameters['query_by'] === 'name' &&
                           $parameters['filter_by'] === 'status:=active';
                })],
                [$this->callback(function (TypesenseQuery $query) {
                    $parameters = $query->getParameters();
                    return $parameters['q'] === 'second search' &&
                           !isset($parameters['query_by']) &&
                           $parameters['filter_by'] === 'status:=active';
                })]
            )
            ->willReturn($expectedResponse);

        $this->specificCollectionFinder->search('first search');
        $result = $this->specificCollectionFinder->search('second search');

        $this->assertSame($expectedResponse, $result);
    }
}