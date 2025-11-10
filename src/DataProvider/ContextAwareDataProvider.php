<?php

namespace ACSEO\TypesenseBundle\DataProvider;

interface ContextAwareDataProvider extends DataProvider
{
    public function supports(string $className);
}
