<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Tests\Transformer;

use ACSEO\TypesenseBundle\Tests\Functional\Entity\Book;
use ACSEO\TypesenseBundle\Tests\Functional\Entity\BookOnline;
use ACSEO\TypesenseBundle\Tests\Functional\Entity\Author;
use ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer;
use Symfony\Component\PropertyAccess\PropertyAccess;
use PHPUnit\Framework\TestCase;

class DoctrineToTypesenseTransformerTest extends TestCase
{


    /**
     * @dataProvider bookData
     */
    public function testConvert($book, $expectedResult)
    {
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $transformer = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor);

        self::assertEquals($expectedResult, $transformer->convert($book));
    }

    public function bookData()
    {
        return [
            [
                new Book(1, 'test', new Author('Nicolas Potier', 'France'), new \Datetime('01/01/1984 00:00:00')),
                [
                    "id" => "1",
                    "sortable_id" => 1,
                    "title" => "test",
                    "author" => "Nicolas Potier",
                    "author_country" => "France",
                    "published_at" => 441763200,
                    "active" => false
                ]
            ],
            [
                new Book(1, 'test', new Author('Nicolas Potier', 'France'), new \DateTimeImmutable('01/01/1984 00:00:00')),
                [
                    "id" => "1",
                    "sortable_id" => 1,
                    "title" => "test",
                    "author" => "Nicolas Potier",
                    "author_country" => "France",
                    "published_at" => 441763200,
                    "active" => false
                ]
            ],
            [
                new Book(1, 'test', new Author('Nicolas Potier', 'France'), new \DateTimeImmutable('01/01/1984 00:00:00'), 'this string will return true'),
                [
                    "id" => "1",
                    "sortable_id" => 1,
                    "title" => "test",
                    "author" => "Nicolas Potier",
                    "author_country" => "France",
                    "published_at" => 441763200,
                    "active" => true
                ]
            ],
            [
                new Book(1, 'test', new Author('Nicolas Potier', 'France'), new \DateTimeImmutable('01/01/1984 00:00:00'), '0'),
                [
                    "id" => "1",
                    "sortable_id" => 1,
                    "title" => "test",
                    "author" => "Nicolas Potier",
                    "author_country" => "France",
                    "published_at" => 441763200,
                    "active" => false
                ]
            ]            

        ];
    }

    public function testChildConvert()
    {
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $propertyAccessor      = PropertyAccess::createPropertyAccessor();
        $transformer           = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor);

        $book                  = new BookOnline(1, 'test', new Author('Nicolas Potier', 'France'), new \Datetime('01/01/1984 00:00:00'));
        $book->setUrl('https://www.acseo.fr');

        self::assertEquals(
            [
                "id" => "1",
                "sortable_id" => 1,
                "title" => "test",
                "author" => "Nicolas Potier",
                "author_country" => "France",
                "published_at" => 441763200,
                'active' => false
            ],
            $transformer->convert($book)
        );

        $book                  = new Book(1, 'test', new Author('Nicolas Potier', 'France'), new \DateTimeImmutable('01/01/1984 00:00:00'));

        self::assertEquals(
            [
                "id" => "1",
                "sortable_id" => 1,
                "title" => "test",
                "author" => "Nicolas Potier",
                "author_country" => "France",
                "published_at" => 441763200,
                'active' => false
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
        $value                  = $transformer->castValue(Book::class, 'published_at', new \Datetime('01/01/1984 00:00:00'));
        self::assertEquals(441763200, $value);
        //DatetimeImmutable
        $value                  = $transformer->castValue(Book::class, 'published_at', new \DatetimeImmutable('01/01/1984 00:00:00'));
        self::assertEquals(441763200, $value);
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
                    'active' => [
                        'name'             => 'active',
                        'type'             => 'bool',
                        'entity_attribute' => 'active'
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