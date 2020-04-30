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
    
    // facet_by
    // max_facet_values
    // num_typos
    // page
    // per_page
    // include_fields	
    // exclude_fields	
    // drop_tokens_threshold	

    private function addParameter($key, $value)
    {
        return $this->searchParameters[$key] = $value;
    }
}