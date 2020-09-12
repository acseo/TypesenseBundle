<?php

namespace ACSEO\TypesenseBundle\Finder;

interface SpecificCollectionFinderInterface
{
    public function search($query, $queryBy);
}
