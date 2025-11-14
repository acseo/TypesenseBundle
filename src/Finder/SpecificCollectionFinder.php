<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Finder;

class SpecificCollectionFinder implements SpecificCollectionFinderInterface 
{

    public function __construct(
        private CollectionFinderInterface $finder, 
        private array $arguments
    )
    { }

    public function search(string $query): TypesenseResponse
    {
        $queryBy = $this->arguments['query_by'] ?? null;
        $query   = new TypesenseQuery($query, $queryBy);
        if ($queryBy != null) {
            unset($this->arguments['query_by']);
        }
        foreach ($this->arguments as $key => $value) {
            $query->addParameter($key, $value);
        }

        return $this->finder->query($query);
    }

    public function rawQuery(TypesenseQuery $query) 
    {
        return $this->finder->rawQuery($query);
    }

    public function query(TypesenseQuery $query)
    {
        return $this->finder->query($query);
    }

    public function hydrateResponse(TypesenseResponse $response)
    {
        return $this->finder->hydrateResponse($response);
    }


}
