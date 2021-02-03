<?php

namespace ACSEO\TypesenseBundle\Command;

use ACSEO\TypesenseBundle\Manager\CollectionManager;
use ACSEO\TypesenseBundle\Manager\DocumentManager;
use ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
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
        ProgressBar::setFormatDefinition(
            'typesense_import',
            '%bar% | %percent:3s%% | %message% %current%/%max%'
        );
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

        $execStart = microtime(true);
        $populated = 0;

        $collectionDefinitions = $this->collectionManager->getCollectionDefinitions();
        foreach ($collectionDefinitions as $collectionDefinition) {
            $collectionName = $collectionDefinition['typesense_name'];
            $class = $collectionDefinition['entity'];

            $q = $this->em->createQuery('select e from '.$class.' e');

            $entities = $q->toIterable();

            $nbEntities = (int) $this->em->createQuery('select COUNT(u.id) from '.$class.' u')->getSingleScalarResult();
            $populated += $nbEntities;

            $progressBar = $io->createProgressBar($nbEntities);

            $progressBar->setFormat('typesense_import');
            $progressBar->setMessage('<info>['.$collectionName.'] '.$class.'</info> ');
            $progressBar->start();

            $data = [];
            foreach ($entities as $entity) {
                $data[] = $this->transformer->convert($entity);
                $io->progressAdvance();
            }

            $this->documentManager->import($collectionName, $data);
        }

        $io->progressFinish();
        $io->success(sprintf(
            '%s element%s populated in %s seconds',
            $populated,
            $populated > 1 ? 's' : '',
            round(microtime(true) - $execStart, PHP_ROUND_HALF_DOWN)
        ));

        return 0;
    }
}