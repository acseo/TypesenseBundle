<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Finder;

class SpecificCollectionFinder
{
    private $finder;
    private $arguments;

    public function __construct(CollectionFinderInterface $finder, array $arguments)
    {
        $this->finder    = $finder;
        $this->arguments = $arguments;
    }

    public function search($query): TypesenseResponse
    {
        $queryBy = $this->arguments['query_by'];
        $query   = new TypesenseQuery($query, $queryBy);
        unset($this->arguments['query_by']);
        foreach ($this->arguments as $key => $value) {
            $query->addParameter($key, $value);
        }

        return $this->finder->query($query);
        //$rawResults = $response->getRawResults();
    }
}
