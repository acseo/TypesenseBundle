<?php

namespace ACSEO\TypesenseBundle\Finder;

use ACSEO\TypesenseBundle\Finder\TypesenseQuery;
use ACSEO\TypesenseBundle\Finder\CollectionFinderInterface;

class SpecificCollectionFinder
{
    private $finder;
    private $arguments;

    public function __construct(CollectionFinderInterface $finder, array $arguments)
    {
        $this->finder = $finder;
        $this->arguments = $arguments;
    }
    
    public function search($query) : TypesenseResponse
    {
        $queryBy = $this->arguments['query_by'];
        $query = new TypesenseQuery($query, $queryBy);
        unset($this->arguments['query_by']);
        foreach ($this->arguments as $key => $value) {
            $query->addParameter($key, $value);
        }

        $response = $this->finder->query($query);
        //$rawResults = $response->getRawResults();

        return $response;
    }
}
