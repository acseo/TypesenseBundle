<?php

namespace ACSEO\TypesenseBundle\Client;

use Typesense\Client;

abstract class AbstractClient
{
    protected ?Client $client = null;
}
