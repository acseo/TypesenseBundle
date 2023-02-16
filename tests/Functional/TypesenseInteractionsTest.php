<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Tests\Functional;

use ACSEO\TypesenseBundle\Client\CollectionClient;
use ACSEO\TypesenseBundle\Client\TypesenseClient;
use ACSEO\TypesenseBundle\Command\CreateCommand;
use ACSEO\TypesenseBundle\Command\ImportCommand;
use ACSEO\TypesenseBundle\EventListener\TypesenseIndexer;
use ACSEO\TypesenseBundle\Finder\CollectionFinder;
use ACSEO\TypesenseBundle\Finder\TypesenseQuery;
use ACSEO\TypesenseBundle\Manager\CollectionManager;
use ACSEO\TypesenseBundle\Manager\DocumentManager;
use ACSEO\TypesenseBundle\Tests\Functional\Entity\Author;
use ACSEO\TypesenseBundle\Tests\Functional\Entity\Book;
use ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * This test ensure that the commands works great with a
 * booted Typesense Server.
 */
class TypesenseInteractionsTest extends KernelTestCase
{
    public const NB_BOOKS    = 5;
    public const BOOK_TITLES = [
        'Total Khéops',
        'Chourmo',
        'Solea',
        'La fabrique du monstre',
        'La chute du monstre',
    ];

    public function testCreateCommand()
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['-vvv']);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Deleting books', $output);
        self::assertStringContainsString('Creating books', $output);
    }

    /**
     * @depends testCreateCommand
     * @dataProvider importCommandProvider
     */
    public function testImportCommand($nbBooks, $maxPerPage = null, $firstPage = null, $lastPage = null, $expectedCount = null)
    {
        $commandTester = $this->importCommandTester([
            'nbBooks' => $nbBooks,
            'maxPerPage' => $maxPerPage,
            'firstPage' => $firstPage,
            'lastPage' => $lastPage
        ]);
        
        $commandOptions = ['-vvv'];

        if ($maxPerPage != null) {
            $commandOptions['--max-per-page'] = $maxPerPage;
        }
        if ($firstPage != null) {
            $commandOptions['--first-page'] = $firstPage;
        } 
        if ($lastPage != null) {
            $commandOptions['--last-page'] = $lastPage;
        } 

        $commandTester->execute($commandOptions);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        
        self::assertStringContainsString(
            sprintf('[books] ACSEO\TypesenseBundle\Tests\Functional\Entity\Book %s entries to insert', $nbBooks), 
            $output
        );
        self::assertStringContainsString(
            sprintf('[OK] %s elements populated', $expectedCount ?? $nbBooks), 
            $output
        );
    }

    public function importCommandProvider()
    {
        return [
            "insert 10 books one by one" => [
                10, 1
            ],
            "insert 42 books 10 by 10" => [
                42, 10
            ],
            "insert 130 books 100 per 100" => [
                130, null //100 is by defaut
            ],
            "insert 498 books 50 per 50, from page 8 to 10 and expect 148 inserted" => [
                498, 50, 8, 10, 148
            ]
        ];
    }

    /**
     * @depends testImportCommand
     * @dataProvider importCommandProvider
     */
    public function testSearchByAuthor($nbBooks)
    {
        $typeSenseClient       = new TypesenseClient($_ENV['TYPESENSE_URL'], $_ENV['TYPESENSE_KEY']);
        $collectionClient      = new CollectionClient($typeSenseClient);
        $book                  = new Book(1, 'test', new Author('Nicolas Potier', 'France'), new \DateTime());
        $em                    = $this->getMockedEntityManager([$book]);
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $bookDefinition        = $collectionDefinitions['books'];

        $bookFinder = new CollectionFinder($collectionClient, $em, $bookDefinition);
        $query = new TypesenseQuery('Nicolas', 'author');

        $query->maxHits($nbBooks < 250 ? $nbBooks : 250);
        $query->perPage($nbBooks < 250 ? $nbBooks : 250);
        
        $results    = $bookFinder->rawQuery($query)->getResults();

        self::assertCount(($nbBooks < 250 ? $nbBooks : 250), $results, "result doesn't contains ".$nbBooks.' elements');
        self::assertArrayHasKey('document', $results[0], "First item does not have the key 'document'");
        self::assertArrayHasKey('highlights', $results[0], "First item does not have the key 'highlights'");
        self::assertArrayHasKey('text_match', $results[0], "First item does not have the key 'text_match'");
    }

    /**
     * @depends testImportCommand
     * @dataProvider importCommandProvider
     */
    public function testSearchByTitle($nbBooks)
    {
        $typeSenseClient  = new TypesenseClient($_ENV['TYPESENSE_URL'], $_ENV['TYPESENSE_KEY']);
        $collectionClient = new CollectionClient($typeSenseClient);
        $book             = new Book(1, 'test', new Author('Nicolas Potier', 'France'), new \DateTime());

        $em                    = $this->getMockedEntityManager([$book]);
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $bookDefinition        = $collectionDefinitions['books'];

        $bookFinder = new CollectionFinder($collectionClient, $em, $bookDefinition);
        $query      = new TypesenseQuery(self::BOOK_TITLES[0], 'title');
        $query->numTypos(0);
        $results = $bookFinder->rawQuery($query)->getResults();
        self::assertCount(1, $results, "result doesn't contains 1 elements");
        self::assertArrayHasKey('document', $results[0], "First item does not have the key 'document'");
        self::assertArrayHasKey('highlights', $results[0], "First item does not have the key 'highlights'");
        self::assertArrayHasKey('text_match', $results[0], "First item does not have the key 'text_match'");
    }

    /**
     * @depends testImportCommand
     */
    public function testCreateAndDelete()
    {
        $book = new Book(1000, 'ACSEO', new Author('Nicolas Potier', 'France'), new \DateTime());

        $typeSenseClient       = new TypesenseClient($_ENV['TYPESENSE_URL'], $_ENV['TYPESENSE_KEY']);
        $collectionClient      = new CollectionClient($typeSenseClient);
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $propertyAccessor      = PropertyAccess::createPropertyAccessor();
        $container             = $this->createMock(ContainerInterface::class);
        $transformer           = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor, $container);
        $collectionManager     = new CollectionManager($collectionClient, $transformer, $collectionDefinitions);
        $typeSenseClient       = new TypesenseClient($_ENV['TYPESENSE_URL'], $_ENV['TYPESENSE_KEY']);
        $documentManager       = new DocumentManager($typeSenseClient);
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $bookDefinition        = $collectionDefinitions['books'];
        $em                    = $this->getMockedEntityManager([$book]);
        $bookFinder            = new CollectionFinder($collectionClient, $em, $bookDefinition);

        // Init the listener
        $listener = new TypesenseIndexer($collectionManager, $documentManager, $transformer);
        // Create a LifecycleEventArgs with a book
        $event = $this->getmockedEventCreate($book);

        // First Persist
        $listener->postPersist($event);
        // First Flush
        $listener->postFlush($event);

        $query = new TypesenseQuery('ACSEO', 'title');
        $query->numTypos(0);
        $results = $bookFinder->rawQuery($query)->getResults();
        self::assertCount(1, $results, "result doesn't contains 1 elements");

        // Update the object
        $book->setTitle('MARSEILLE');
        $event = $this->getmockedEventCreate($book);

        // First Update
        $listener->postUpdate($event);
        // Second Flush
        $listener->postFlush($event);

        // We should not find this book title
        $query = new TypesenseQuery('ACSEO', 'title');
        $query->numTypos(0);
        $results = $bookFinder->rawQuery($query)->getResults();
        self::assertCount(0, $results, "result doesn't contains 1 elements");

        // But we should find this title
        $query = new TypesenseQuery('MARSEILLE', 'title');
        $query->numTypos(0);
        $results = $bookFinder->rawQuery($query)->getResults();
        self::assertCount(1, $results, "result doesn't contains 1 elements");

        // First Remove
        $listener->preRemove($event);
        $listener->postRemove($event);
        // Third Flush
        $listener->postFlush($event);

        // We should not find this book title
        $query = new TypesenseQuery('MARSEILLE', 'title');
        $query->numTypos(0);
        $results = $bookFinder->rawQuery($query)->getResults();
        self::assertCount(0, $results, "result doesn't contains 0 elements");
    }

    private function createCommandTester(): CommandTester
    {
        $application = new Application();

        $application->setAutoExit(false);

        $book = $this->getMockBuilder('\App\Entity\Book')->getMock();
        // Author is required
        $author = $this->getMockBuilder('\App\Entity\Author')->getMock();

        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $typeSenseClient       = new TypesenseClient($_ENV['TYPESENSE_URL'], $_ENV['TYPESENSE_KEY']);
        $propertyAccessor      = PropertyAccess::createPropertyAccessor();
        $collectionClient      = new CollectionClient($typeSenseClient);
        $container             = $this->createMock(ContainerInterface::class);
        $transformer           = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor, $container);
        $collectionManager     = new CollectionManager($collectionClient, $transformer, $collectionDefinitions);

        $command = new CreateCommand($collectionManager);

        $application->add($command);

        return new CommandTester($application->find('typesense:create'));
    }

    private function importCommandTester($options): CommandTester
    {
        $application = new Application();

        $application->setAutoExit(false);

        // Prepare all mocked objects required to run the command
        $books                 = $this->getMockedBooks($options);
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $typeSenseClient       = new TypesenseClient($_ENV['TYPESENSE_URL'], $_ENV['TYPESENSE_KEY']);
        $propertyAccessor      = PropertyAccess::createPropertyAccessor();
        $collectionClient      = new CollectionClient($typeSenseClient);
        $container             = $this->createMock(ContainerInterface::class);
        $transformer           = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor, $container);
        $documentManager       = new DocumentManager($typeSenseClient);
        $collectionManager     = new CollectionManager($collectionClient, $transformer, $collectionDefinitions);
        $em                    = $this->getMockedEntityManager($books, $options);

        $command = new ImportCommand($em, $collectionManager, $documentManager, $transformer);

        $application->add($command);

        return new CommandTester($application->find('typesense:import'));
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

    private function getMockedBooks($options)
    {
        $author = new Author('Nicolas Potier', 'France');
        $books  = [];

        $nbBooks = $options['nbBooks'] ?? self::NB_BOOKS;
        for ($i = 0; $i < $nbBooks; ++$i) {
            $books[] = new Book($i, self::BOOK_TITLES[$i] ?? 'Book '.$i, $author, new \DateTime());
        }

        return $books;
    }

    private function getMockedEntityManager($books, array $options = [])
    {
        $em = $this->createMock(EntityManager::class);

        $connection = $this->createMock(Connection::class);
        $em->method('getConnection')->willReturn($connection);

        $configuration = $this->createMock(Configuration::class);
        $connection->method('getConfiguration')->willReturn($configuration);

        $query = $this->createMock(Query::class);
        $em->method('createQuery')->willReturn($query);

        $query->method('getSingleScalarResult')->willReturn(count($books));

        $query->method('setFirstResult')->willReturn($query);
        $query->method('setMaxResults')->willReturn($query);

        // Dirty Method to count number of call to the method toIterable in order to return
        // the good results
        $this->cptToIterableCall = isset($options['firstPage']) ? ($options['firstPage']-1) : 0;
        
        $maxPerPage = $options['maxPerPage'] ?? 100;

        $query->method('toIterable')->will($this->returnCallback(function() use ($books, $maxPerPage){
            $result =  array_slice($books, 
                $this->cptToIterableCall * $maxPerPage,
                $maxPerPage
            );
            $this->cptToIterableCall++;

            return $result;
        }));

        return $em;
    }

    /**
     * mock a lifeCycleEventArgs Object.
     *
     * @param $eventType
     */
    private function getmockedEventCreate($book): \PHPUnit\Framework\MockObject\MockObject
    {
        $lifeCycleEvent = $this->createMock(LifecycleEventArgs::class);
        $lifeCycleEvent->method('getObject')->willReturn($book);

        return $lifeCycleEvent;
    }
}
