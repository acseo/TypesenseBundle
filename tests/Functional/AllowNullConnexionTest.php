<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Tests\Functional;

use ACSEO\TypesenseBundle\Client\CollectionClient;
use ACSEO\TypesenseBundle\Client\TypesenseClient;
use ACSEO\TypesenseBundle\Command\CreateCommand;
use ACSEO\TypesenseBundle\Command\ImportCommand;
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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * This test ensure that the commands works great with a
 * booted Typesense Server.
 */
class AllowNullConnexionTest extends KernelTestCase
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
     */
    public function testImportCommand()
    {
        $commandTester = $this->importCommandTester();
        $commandTester->execute([]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('[books] ACSEO\TypesenseBundle\Tests\Functional\Entity\Book', $output);
        self::assertStringContainsString('[OK] '.self::NB_BOOKS.' elements populated', $output);
    }

    /**
     * @depends testImportCommand
     */
    public function testSearchByAuthor()
    {
        $typeSenseClient       = new TypesenseClient('null', 'null');
        $collectionClient      = new CollectionClient($typeSenseClient);
        $book                  = new Book(1, 'test', new Author('Nicolas Potier', 'France'), new \DateTime());
        $em                    = $this->getMockedEntityManager([$book]);
        $collectionDefinitions = $this->getCollectionDefinitions(\get_class($book));
        $bookDefinition        = $collectionDefinitions['books'];

        $bookFinder = new CollectionFinder($collectionClient, $em, $bookDefinition);
        $results    = $bookFinder->rawQuery(new TypesenseQuery('Nicolas', 'author'))->getResults();
        self::assertNull($results);
    }

    private function createCommandTester(): CommandTester
    {
        $application = new Application();

        $application->setAutoExit(false);

        $book = $this->getMockBuilder('\App\Entity\Book')->getMock();
        // Author is required
        $author = $this->getMockBuilder('\App\Entity\Author')->getMock();

        $collectionDefinitions = $this->getCollectionDefinitions(\get_class($book));
        $typeSenseClient       = new TypesenseClient('null', 'null');
        $propertyAccessor      = PropertyAccess::createPropertyAccessor();
        $collectionClient      = new CollectionClient($typeSenseClient);
        $container             = $this->createMock(ContainerInterface::class);
        $transformer           = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor, $container);
        $collectionManager     = new CollectionManager($collectionClient, $transformer, $collectionDefinitions);

        $command = new CreateCommand($collectionManager);

        $application->add($command);

        return new CommandTester($application->find('typesense:create'));
    }

    private function importCommandTester(): CommandTester
    {
        $application = new Application();

        $application->setAutoExit(false);

        // Prepare all mocked objects required to run the command
        $books                 = $this->getMockedBooks();
        $collectionDefinitions = $this->getCollectionDefinitions(Book::class);
        $typeSenseClient       = new TypesenseClient('null', 'null');
        $propertyAccessor      = PropertyAccess::createPropertyAccessor();
        $collectionClient      = new CollectionClient($typeSenseClient);
        $container             = $this->createMock(ContainerInterface::class);
        $transformer           = new DoctrineToTypesenseTransformer($collectionDefinitions, $propertyAccessor, $container);
        $documentManager       = new DocumentManager($typeSenseClient);
        $collectionManager     = new CollectionManager($collectionClient, $transformer, $collectionDefinitions);
        $em                    = $this->getMockedEntityManager($books);

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

    private function getMockedBooks()
    {
        $author = new Author('Nicolas Potier', 'France');
        $books  = [];

        for ($i = 0; $i < self::NB_BOOKS; ++$i) {
            $books[] = new Book($i, self::BOOK_TITLES[$i], $author, new \DateTime());
        }

        return $books;
    }

    private function getMockedEntityManager($books)
    {
        $em = $this->createMock(EntityManager::class);

        $connection = $this->createMock(Connection::class);
        $em->method('getConnection')->willReturn($connection);

        $configuration = $this->createMock(Configuration::class);
        $connection->method('getConfiguration')->willReturn($configuration);

        $query = $this->createMock(Query::class);
        $em->method('createQuery')->willReturn($query);

        $query->method('setFirstResult')->willReturn($query);
        $query->method('setMaxResults')->willReturn($query);

        $query->method('getSingleScalarResult')->willReturn(self::NB_BOOKS);

        $query->method('toIterable')->willReturn($books);

        return $em;
    }
}
