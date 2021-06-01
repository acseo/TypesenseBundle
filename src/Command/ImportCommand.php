<?php

namespace ACSEO\TypesenseBundle\Command;

use ACSEO\TypesenseBundle\Manager\CollectionManager;
use ACSEO\TypesenseBundle\Manager\DocumentManager;
use ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportCommand extends Command
{
    protected static $defaultName = 'typesense:import';

    private $em;
    private $collectionManager;
    private $documentManager;
    private $transformer;

    public function __construct(
        EntityManagerInterface $em,
        CollectionManager $collectionManager,
        DocumentManager $documentManager,
        DoctrineToTypesenseTransformer $transformer
    ) {
        parent::__construct();
        $this->em = $em;
        $this->collectionManager = $collectionManager;
        $this->documentManager = $documentManager;
        $this->transformer = $transformer;
    }

    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Import collections from Database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $io->progressStart();

        $collectionDefinitions = $this->collectionManager->getCollectionDefinitions();
        foreach ($collectionDefinitions as $collectionDefinition) {
            $collectionName = $collectionDefinition['typesense_name'];

            $repository = $this->em->getRepository($collectionDefinition['entity']);
            $entities = $repository->findAll();
            $data = [];
            foreach ($entities as $entity) {
                $data[] = $this->transformer->convert($entity);

                $io->progressAdvance();
            }

            $this->documentManager->import($collectionName, $data);
        }

        $io->progressFinish();
        $io->success('Done !');

        return 0;
    }
}
