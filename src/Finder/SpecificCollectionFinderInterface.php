<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Finder;

interface SpecificCollectionFinderInterface
{
    public function search(string $query);

    public function rawQuery(TypesenseQuery $query);

    public function query(TypesenseQuery $query);

    public function hydrateResponse(TypesenseResponse $response);
}
