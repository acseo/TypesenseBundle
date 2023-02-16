<?php

declare(strict_types=1);

namespace ACSEO\Bundle\TypesenseBundle\Tests\Unit\EventListener;

use ACSEO\TypesenseBundle\Client\CollectionClient;
use ACSEO\TypesenseBundle\Manager\DocumentManager;
use ACSEO\TypesenseBundle\EventListener\TypesenseIndexer;
use ACSEO\TypesenseBundle\Manager\CollectionManager;
use ACSEO\TypesenseBundle\Tests\Functional\Entity\Book;
use ACSEO\TypesenseBundle\Tests\Functional\Entity\Author;
use ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class TypesenseIndexerTest extends TestCase
{
    use ProphecyTrait;

    public function setUp(): void
    {
        $this->objectManager = $this->prophesize(ObjectManager::class);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    private function initialize($collectionDefinitions)
    {
        $transformer = new DoctrineToTypesenseTransformer($collectionDefinitions, $this->propertyAccessor, $this->container->reveal());

        $collectionClient = $this->prophesize(CollectionClient::class);

        $collectionManager = new CollectionManager($collectionClient->reveal(), $transformer, $collectionDefinitions);
        $this->documentManager = $this->prophesize(DocumentManager::class);

        $this->eventListener = new TypesenseIndexer($collectionManager, $this->documentManager->reveal(), $transformer);
    }

    /**
     * @dataProvider postUpdateProvider
     */
    public function testPostUpdate($prefix)
    {
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class, $prefix);

        $this->initialize($collectionDefinitions);

        $book = new Book(1, 'The Doors of Perception', new Author('Aldoux Huxley', 'United Kingdom'), new \DateTime('1954-01-01'));

        $eventArgs = new LifecycleEventArgs($book, $this->objectManager->reveal());

        $this->eventListener->postUpdate($eventArgs);
        $this->eventListener->postFlush();

        $this->documentManager->delete(sprintf('%sbooks', $prefix), 1)->shouldHaveBeenCalled();
        $this->documentManager->index(sprintf('%sbooks', $prefix), [
            'id' => 1,
            'sortable_id' => 1,
            'title' => 'The Doors of Perception',
            'author' => 'Aldoux Huxley',
            'author_country' => 'United Kingdom',
            'published_at' => -504921600,
        ])->shouldHaveBeenCalled();
    }

    public function postUpdateProvider()
    {
        return [
            [ '' ],
            [ 'foo_' ],
        ];
    }

    private function getCollectionDefinitions($entityClass, $prefix = '')
    {
        return [
            'books' => [
                'typesense_name' => sprintf('%sbooks', $prefix),
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
