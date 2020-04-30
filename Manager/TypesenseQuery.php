<?php

namespace ACSEO\TypesenseBundle\Manager;

class TypesenseQuery
{
    private $searchParameters;

    public function __construct(string $q, string $queryBy)
    {
        $this->searchParameters = [];
        $this->addParameter('q', $q);
        $this->addParameter('query_by', $queryBy);
    }

    public function getParameters()
    {
        return $this->searchParameters;
    }
    
    public function prefix(bool $prefix)
    {
        return $this->addParameter('prefix', $prefix);
    }

    public function filterBy(string $filterBy)
    {
        return $this->addParameter('filter_by', $filterBy);
    }

    public function sortBy(string $sortBy)
    {
        return $this->addParameter('sort_by', $sortBy);
    }

    public function facetBy(string $facetBy)
    {
        return $this->addParameter('facet_by', $facetBy);
    }

    public function maxFacetValues(int $maxFacetValues)
    {
        return $this->addParameter('max_facet_values', $maxFacetValues);
    }

    public function numTypos(int $numTypos)
    {
        return $this->addParameter('num_typos', $numTypos);
    }

    public function page(int $page)
    {
        return $this->addParameter('page', $page);
    }

    public function perPage(int $perPage)
    {
        return $this->addParameter('per_page', $perPage);
    }

    public function includeFields(string $includeFields)
    {
        return $this->addParameter('include_fields', $includeFields);
    }

    public function excludeFields(string $excludeFields)
    {
        return $this->addParameter('exclude_fields', $excludeFields);
    }

    public function dropTokensThreshold(int $dropTokensThreshold)
    {
        return $this->addParameter('drop_tokens_threshold', $dropTokensThreshold);
    }

    private function addParameter($key, $value)
    {
        return $this->searchParameters[$key] = $value;
    }
}
