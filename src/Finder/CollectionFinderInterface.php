<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Finder;

interface CollectionFinderInterface
{
    public function rawQuery(TypesenseQuery $query);

    public function query(TypesenseQuery $query);
}
