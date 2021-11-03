<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Finder;

interface SpecificCollectionFinderInterface
{
    public function search($query, $queryBy);
}
