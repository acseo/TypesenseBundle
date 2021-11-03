<?php

declare(strict_types=1);

namespace ACSEO\Bundle\TypesenseBundle\Tests\Unit\Finder;

use ACSEO\TypesenseBundle\Finder\TypesenseResponse;
use PHPUnit\Framework\TestCase;

class TypesenseResponseTest extends TestCase
{
    private function constructResponse()
    {
        $result                   = [];
        $result['facet_counts']   = 0;
        $result['found']          = true;
        $result['hits']           = [];
        $result['page']           = 1;
        $result['search_time_ms'] = 0;

        return new TypesenseResponse($result);
    }

    public function testConstructor()
    {
        $this->constructResponse();
    }

    public function testGetters()
    {
        $response = $this->constructResponse();

        self::assertEquals(0, $response->getFacetCounts());
        self::assertEquals([], $response->getRawResults());
    }
}
