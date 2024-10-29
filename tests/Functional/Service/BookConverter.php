<?php

namespace ACSEO\TypesenseBundle\Tests\Functional\Service;

use ACSEO\TypesenseBundle\Tests\Functional\Entity\Book;

class BookConverter
{
    /**
     * In a real life example maybe you would need to call some extra service to get this URL
     * and that service can be injected with DI into this one
     */
    public function getCoverImageURL(Book $book) : string
    {
        return sprintf('http://fake.image/%d', $book->getId());
    }
}
