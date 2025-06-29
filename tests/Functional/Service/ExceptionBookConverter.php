<?php

namespace ACSEO\TypesenseBundle\Tests\Functional\Service;

use ACSEO\TypesenseBundle\Tests\Functional\Entity\Book;

class ExceptionBookConverter
{
    /**
     * In a real life example maybe you would need to call some extra service to get this URL
     * and that service can be injected with DI into this one
     */
    public function getCoverImageURL($book) : string
    {
        throw new \Exception("I'm trowing an exception during conversion");
    }
}
