<?php

namespace App\Tests\Command;

use ACSEO\TypesenseBundle\Client\CollectionClient;
use ACSEO\TypesenseBundle\Client\TypesenseClient;
use ACSEO\TypesenseBundle\Command\CreateCommand;
use ACSEO\TypesenseBundle\Command\ImportCommand;
use ACSEO\TypesenseBundle\Finder\CollectionFinder;
use ACSEO\TypesenseBundle\Finder\TypesenseQuery;
use ACSEO\TypesenseBundle\Manager\CollectionManager;
use ACSEO\TypesenseBundle\Manager\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;
use ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\AbstractQuery;

/**
 * This test ensure that the commands works great with a
 * booted Typesense Server
 *
 */
class TypesenseInteractionsTest extends KernelTestCase
{
    public function testCreateCommand()
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['-vvv']);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Deleting books", $output);
        $this->assertStringContainsString("Creating books", $output);
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
        $this->assertStringContainsString("Import [books]", $output);
        $this->assertStringContainsString("[OK] 4 elements populated", $output);
    }
    
    /**
     * @depends testImportCommand
     */
    public function testSearchByAuthor()
    {
        $typeSenseClient = new TypesenseClient($_ENV['TYPESENSE_URL'], $_ENV['TYPESENSE_KEY']);
        $collectionClient = new CollectionClient($typeSenseClient);
        $book = $this->getMockBuilder('\App\Entity\Book')->getMock();

        $em = $this->getMockedEntityManager($book);
        $book = $this->getMockBuilder('\App\Entity\Book')->getMock();
        $collectionDefinitions = $this->getCollectionDefinitions(get_class($book));
        $bookDefinition = $collectionDefinitions['books'];

        $bookFinder = new CollectionFinder($collectionClient, $em, $bookDefinition);
        $results = $bookFinder->rawQuery(new TypesenseQuery('Nicolas', 'author'))->getResults();
        $this->assertCount(4, $results, "result doesn't contains 4 elements");
        $this->assertArrayHasKey('document', $results[0], "First item does not have the key 'document'");
        $this->assertArrayHasKey('highlights', $results[0], "First item does not have the key 'highlights'");
        $this->assertArrayHasKey('text_match', $results[0], "First item does not have the key 'text_match'");
    }

    /**
     * @depends testImportCommand
     */
    public function testSearchByTitle()
    {
        $typeSenseClient = new TypesenseClient($_ENV['TYPESENSE_URL'], $_ENV['TYPESENSE_KEY']);
        $collectionClient = new CollectionClient($typeSenseClient);
        $book = $this->getMockBuilder('\App\Entity\Book')->getMock();

        $em = $this->getMockedEntityManager($book);
        $book = $this->getMockBuilder('\App\Entity\Book')->getMock();
        $collectionDefinitions = $this->getCollectionDefinitions(get_class($book));
        $bookDefinition = $collectionDefinitions['books'];

        $bookFinder = new CollectionFinder($collectionClient, $em, $bookDefinition);
        $query = new TypesenseQuery('One', 'title');
        $query->numTypos(0);
        $results = $bookFinder->rawQuery($query)->getResults();
        $this->assertCount(1, $results, "result doesn't contains 1 elements");
        $this->assertArrayHasKey('document', $results[0], "First item does not have the key 'document'");
        $this->assertArrayHasKey('highlights', $results[0], "First item does not have the key 'highlights'");
        $this->assertArrayHasKey('text_match', $results[0], "First item does not have the key 'text_match'");
    }

    /**
     * @return CommandTester
     */
    private function createCommandTester()
    {
        $application = new Application();

        $application->setAutoExit(false);

        $book = $this->getMockBuilder('\App\Entity\Book')->getMock();
        // Author is required
        $author = $this->getMockBuilder('\App\Entity\Author')->getMock();

        $collectionDefinitions = $this->getCollectionDefinitions(get_class($book));
        $typeSenseClient = new TypesenseClient($_ENV['TYPESENSE_URL'], $_ENV['TYPESENSE_KEY']);
        $collectionClient = new CollectionClient($typeSenseClient);
        $transformer = new DoctrineToTypesenseTransformer($collectionDefinitions);
        $collectionManager = new CollectionManager($collectionClient, $transformer, $collectionDefinitions);

        $command = new CreateCommand($collectionManager);

        $application->add($command);

        return new CommandTester($application->find('typesense:create'));
    }

    /**
     * @return CommandTester
     */
    private function importCommandTester()
    {
        $application = new Application();

        $application->setAutoExit(false);

        // Prepare all mocked objects required to run the command
        $book = $this->getMockedBook();
        $collectionDefinitions = $this->getCollectionDefinitions(get_class($book));
        $typeSenseClient = new TypesenseClient($_ENV['TYPESENSE_URL'], $_ENV['TYPESENSE_KEY']);
        $collectionClient = new CollectionClient($typeSenseClient);
        $transformer = new DoctrineToTypesenseTransformer($collectionDefinitions);
        $documentManager = new DocumentManager($typeSenseClient);
        $collectionManager = new CollectionManager($collectionClient, $transformer, $collectionDefinitions);
        $em = $this->getMockedEntityManager($book);

        $command = new ImportCommand($em, $collectionManager, $documentManager, $transformer);

        $application->add($command);

        return new CommandTester($application->find('typesense:import'));
    }

    private function getCollectionDefinitions($entityClass)
    {
        return [
            "books" => [
                "typesense_name" => "books",
                "entity" => $entityClass,
                "name" => "books",
                "fields" => [
                        "id" => [
                        "name" => "id",
                        "type" => "primary",
                        "entity_attribute" => "id",
                    ],
                    "sortable_id" => [
                        "entity_attribute" => "id",
                        "name" => "sortable_id",
                        "type" => "int32",
                    ],
                    "title" => [
                        "name" => "title",
                        "type" => "string",
                        "entity_attribute" => "title",
                    ],
                    "author" => [
                        "name" => "author",
                        "type" => "object",
                        "entity_attribute" => "author",
                    ],
                    "author.country" => [
                        "name" => "author_country",
                        "type" => "string",
                        "entity_attribute" => "author.country",
                    ],
                ],
                "default_sorting_field" => "sortable_id"
            ]
        ];
    }

    private function getMockedBook()
    {
        $book = $this->getMockBuilder('\App\Entity\Book')
        ->addMethods(['getId', 'getTitle', 'getAuthor'])
        ->getMock();
        
        $author = $this->getMockBuilder('\App\Entity\Author')
                ->addMethods(['__toString', 'getCountry'])
                ->getMock();

        $author->method('__toString')->willReturn('Nicolas Potier');

        // getId is called Twice, for id and sortable_id fields
        $book->method('getId')->willReturn(
            $this->onConsecutiveCalls(
            1,
            1,
            2,
            2,
            3,
            3,
            4,
            4
        )
        );
        $book->method('getTitle')->willReturn(
            $this->onConsecutiveCalls(
            'Sample Book One',
            'Sample Book Two',
            'Sample Book Three',
            'Sample Book Four'
        )
        );
        $author->method('getCountry')->willReturn('France');
        $book->method('getAuthor')->willReturn($author);

        return $book;
    }

    private function getMockedEntityManager($book)
    {
        $em = $this->createMock(EntityManager::class);

        $connection = $this->createMock(Connection::class);
        $em->method('getConnection')->willReturn($connection);

        $configuration = $this->createMock(Configuration::class);
        $connection->method('getConfiguration')->willReturn($configuration);

        $query = $this->createMock(AbstractQuery::class);
        $em->method('createQuery')->willReturn($query);

        $query->method('getSingleScalarResult')->willReturn(4);

        $query->method('toIterable')->willReturn([
            $book,
            $book,
            $book,
            $book
        ]);

        return $em;
    }
}
