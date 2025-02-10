<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Tests\Transformer;

use ACSEO\TypesenseBundle\Tests\Functional\Entity\Book;
use ACSEO\TypesenseBundle\Tests\Functional\Entity\BookOnline;
use ACSEO\TypesenseBundle\Tests\Functional\Entity\Author;
use ACSEO\TypesenseBundle\Tests\Functional\Service\BookConverter;
use ACSEO\TypesenseBundle\Tests\Functional\Service\ExceptionBookConverter;
use ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer;
use Symfony\Component\DependencyInjection\Container;
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
        $container             = $this->getContainerInstance();
        $transformer = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor, $container);

        self::assertEquals($expectedResult, $transformer->convert($book));
    }

    public function bookData()
    {
        return [
            [
                new Book(1, 'test', null, new \Datetime('01/01/1984 00:00:00')),
                [
                    "id" => "1",
                    "sortable_id" => 1,
                    "title" => "test",
                    "author" => null,
                    "author_country" => null,
                    "published_at" => 441763200,
                    "active" => false,
                    "cover_image_url" => "http://fake.image/1"
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
                    "active" => false,
                    "cover_image_url" => "http://fake.image/1"
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
                    "active" => true,
                    "cover_image_url" => "http://fake.image/1"
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
                    "active" => false,
                    "cover_image_url" => "http://fake.image/1"
                ]
            ]            

        ];
    }

    public function testChildConvert()
    {
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $propertyAccessor      = PropertyAccess::createPropertyAccessor();
        $container             = $this->getContainerInstance();
        $transformer           = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor, $container);

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
                "cover_image_url" => "http://fake.image/1",
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
                "cover_image_url" => "http://fake.image/1",
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
        $container             = $this->getContainerInstance();
        $transformer           = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor, $container);
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
        $container             = $this->getContainerInstance();
        $transformer           = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor, $container);

        // Conversion OK
        $author                 = new Author('Nicolas Potier', 'France');
        $value                  = $transformer->castValue(Book::class, 'author', $author);
        self::assertEquals($author->__toString(), $value);

        // Conversion KO
        $this->expectExceptionMessage('Call to undefined method ArrayObject::__toString()');
        $value                  = $transformer->castValue(Book::class, 'author', new \ArrayObject());
    }


    public function testCastValueViaService()
    {
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $container             = $this->getContainerInstance();
        $transformer = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor, $container);

        $book = new Book(1, 'test', new Author('Nicolas Potier', 'France'), new \Datetime('01/01/1984 00:00:00'));
        $expectedResult = [
            "id" => "1",
            "sortable_id" => 1,
            "title" => "test",
            "author" => "Nicolas Potier",
            "author_country" => "France",
            "published_at" => 441763200,
            "active" => false,
            "cover_image_url" => "http://fake.image/1"
        ];
        self::assertEquals($expectedResult, $transformer->convert($book));

        // Override collectionDefinition to declare a wrong service converter
        $collectionDefinitions['books']['fields']['cover_image_url']['entity_attribute'] = 'This service does not exists';
        $transformer = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor, $container);
        $expectedResult = [
            "id" => "1",
            "sortable_id" => 1,
            "title" => "test",
            "author" => "Nicolas Potier",
            "author_country" => "France",
            "published_at" => 441763200,
            "active" => false,
            "cover_image_url" => null
        ];
        
        self::assertEquals($expectedResult, $transformer->convert($book));
        
        // Override collectionDefinition to declare a service that throws exception
        $collectionDefinitions['books']['fields']['cover_image_url']['entity_attribute'] = 'ACSEO\TypesenseBundle\Tests\Functional\Service\ExceptionBookConverter::getCoverImageURL';
        $transformer = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor, $container);
        $this->expectExceptionMessage("I'm trowing an exception during conversion");
        $result = $transformer->convert($book);

        // Override collectionDefinition to declare a service that throws exception
        $collectionDefinitions['books']['fields']['cover_image_url']['entity_attribute'] = 'ACSEO\TypesenseBundle\Tests\Functional\Service\BookConverter::thisMethodDoesNotExists';
        $transformer = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor, $container);
        self::assertEquals($expectedResult, $transformer->convert($book));
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
                        'optional'         => true
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
                    'cover_image_url' => [
                        'name'             => 'cover_image_url',
                        'type'             => 'string',
                        'optional'         => true,
                        'entity_attribute' => 'ACSEO\TypesenseBundle\Tests\Functional\Service\BookConverter::getCoverImageURL',
                    ]
                ],
                'default_sorting_field' => 'sortable_id',
            ],
        ];
    }

    private function getContainerInstance()
    {
        $containerInstance = new Container();
        $containerInstance->set('ACSEO\TypesenseBundle\Tests\Functional\Service\BookConverter', new BookConverter());
        $containerInstance->set('ACSEO\TypesenseBundle\Tests\Functional\Service\ExceptionBookConverter', new ExceptionBookConverter());
        return $containerInstance;
    }
}
