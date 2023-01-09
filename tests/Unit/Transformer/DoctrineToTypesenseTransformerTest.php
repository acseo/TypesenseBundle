<?php

declare(strict_types=1);

namespace ACSEO\Bundle\TypesenseBundle\Tests\Transformer;

use ACSEO\TypesenseBundle\Client\CollectionClient;
use ACSEO\TypesenseBundle\Client\TypesenseClient;
use ACSEO\TypesenseBundle\Tests\Functional\Entity\Book;
use ACSEO\TypesenseBundle\Tests\Functional\Entity\Author;
use ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer;
use Symfony\Component\PropertyAccess\PropertyAccess;
use PHPUnit\Framework\TestCase;

class DoctrineToTypesenseTransformerTest extends TestCase
{

    public function testConvert()
    {
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $propertyAccessor      = PropertyAccess::createPropertyAccessor();
        $transformer           = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor);

        $book                  = new Book(1, 'test', new Author('Nicolas Potier', 'France'), new \Datetime('02/10/1984'));
        self::assertEquals(
            [
                "id" => "1",
                "sortable_id" => 1,
                "title" => "test",
                "author" => "Nicolas Potier",
                "author_country" => "France",
                "published_at" => 445219200
            ],
            $transformer->convert($book)
        );   
        
        $book                  = new Book(1, 'test', new Author('Nicolas Potier', 'France'), new \DateTimeImmutable('02/10/1984'));

        self::assertEquals(
            [
                "id" => "1",
                "sortable_id" => 1,
                "title" => "test",
                "author" => "Nicolas Potier",
                "author_country" => "France",
                "published_at" => 445219200
            ],
            $transformer->convert($book)
        );          
    }

    public function testCastValueDatetime()
    {
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $propertyAccessor      = PropertyAccess::createPropertyAccessor();
        $transformer           = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor);
        //Datetime
        $value                  = $transformer->castValue(Book::class, 'published_at', new \Datetime('02/10/1984'));
        self::assertEquals(445219200, $value);
        //DatetimeImmutable
        $value                  = $transformer->castValue(Book::class, 'published_at', new \DatetimeImmutable('02/10/1984'));
        self::assertEquals(445219200, $value);
    }

    public function testCastValueObject()
    {
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $propertyAccessor      = PropertyAccess::createPropertyAccessor();
        $transformer           = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor);
        
        // Conversion OK
        $author                 = new Author('Nicolas Potier', 'France');
        $value                  = $transformer->castValue(Book::class, 'author', $author);
        self::assertEquals($author->__toString(), $value);

        // Conversion KO
        $this->expectExceptionMessage('Call to undefined method ArrayObject::__toString()');
        $value                  = $transformer->castValue(Book::class, 'author', new \ArrayObject());
    }

    private function getCollectionDefinitions($entityClass)
    {
        return [
            'books' => [
                'typesense_name' => 'books',
                'entity'         => $entityClass,
                'name'           => 'books',
                'fields'         => [
                    'id' => [
                        'name'             => 'id',
                        'type'             => 'primary',
                        'entity_attribute' => 'id',
                    ],
                    'sortable_id' => [
                        'entity_attribute' => 'id',
                        'name'             => 'sortable_id',
                        'type'             => 'int32',
                    ],
                    'title' => [
                        'name'             => 'title',
                        'type'             => 'string',
                        'entity_attribute' => 'title',
                    ],
                    'author' => [
                        'name'             => 'author',
                        'type'             => 'object',
                        'entity_attribute' => 'author',
                    ],
                    'michel' => [
                        'name'             => 'author_country',
                        'type'             => 'string',
                        'entity_attribute' => 'author.country',
                    ],
                    'publishedAt' => [
                        'name'             => 'published_at',
                        'type'             => 'datetime',
                        'optional'         => true,
                        'entity_attribute' => 'publishedAt',
                    ],
                ],
                'default_sorting_field' => 'sortable_id',
            ],
        ];
    }
}