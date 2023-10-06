<?php

namespace ACSEO\TypesenseBundle\DataProvider;

interface DataProvider
{
    public function getData(string $className,  int $page, int $maxPerPage): iterable;
}
