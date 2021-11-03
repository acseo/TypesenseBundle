<?php

declare(strict_types=1);

namespace ACSEO\Bundle\TypesenseBundle\Tests\Unit\Finder;

use ACSEO\TypesenseBundle\Finder\TypesenseQuery;
use PHPUnit\Framework\TestCase;

class TypesenseQueryTest extends TestCase
{
    public function testSimpleQuery()
    {
        $query = new TypesenseQuery('search term', 'search field');

        self::assertEquals(
            ['q' => 'search term', 'query_by' => 'search field'],
            $query->getParameters()
        );
    }

    public function testComplexQuery()
    {
        $query = (new TypesenseQuery('search term', 'search field'))
            ->prefix(false)
            ->filterBy('filter term')
            ->sortBy('sort term')
            ->facetBy('facet term')
            ->maxFacetValues(10)
            ->numTypos(0)
            ->page(1)
            ->perPage(20)
            ->includeFields('field1,field2')
            ->excludeFields('field3,field4')
            ->dropTokensThreshold(0)
        ;

        self::assertEquals(
            [
                'q'                     => 'search term',
                'query_by'              => 'search field',
                'prefix'                => false,
                'filter_by'             => 'filter term',
                'sort_by'               => 'sort term',
                'facet_by'              => 'facet term',
                'max_facet_values'      => 10,
                'num_typos'             => 0,
                'page'                  => 1,
                'per_page'              => 20,
                'include_fields'        => 'field1,field2',
                'exclude_fields'        => 'field3,field4',
                'drop_tokens_threshold' => 0,
            ],
            $query->getParameters()
        );
    }
}
