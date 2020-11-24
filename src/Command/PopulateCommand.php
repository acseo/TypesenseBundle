<?php

namespace ACSEO\TypesenseBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use ACSEO\TypesenseBundle\Manager\CollectionManager;
use ACSEO\TypesenseBundle\Manager\DocumentManager;
use ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer;

class PopulateCommand extends Command
{
    protected static $defaultName = 'typesense:populate';

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
            'typesense_populate',
            '%bar% | %percent:3s%% | %message% %current%/%max%'
        );
    }

    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Populates collections from Database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $populated = 0;
        $execStart = microtime(true);
        $io->newLine();

        $collectionDefinitions = $this->collectionManager->getCollectionDefinitions();
        foreach ($collectionDefinitions as $collectionDefinition) {
            $collectionName = $collectionDefinition['typesense_name'];
            $class = $collectionDefinition['entity'];

            $q = $this->em->createQuery('select u from '.$class.' u');
            $entities = $q->iterate();

            $progressBar = $io->createProgressBar(
                (int) $this->em->createQuery('select COUNT(u.id) from '.$class.' u')->getSingleScalarResult()
            );

            $progressBar->setFormat('typesense_populate');
            $progressBar->setMessage('<info>['.$collectionName.'] '.$class.'</info> ');
            $progressBar->start();

            foreach ($entities as $entity) {
                $data = $this->transformer->convert($entity[0]);
                try {
                    $this->documentManager->delete($collectionName, $data['id']);
                } catch (\Exception $e) {
                    // Silence is gold
                }

                $this->documentManager->index($collectionName, $data);
                $progressBar->advance();
                $populated++;
            }

            $progressBar->finish();
            $io->newLine();
        }

        $io->newLine();
        $io->success(sprintf(
            '%s element%s populated in %s seconds',
            $populated,
            $populated > 1 ? 's' : '',
            round(microtime(true) - $execStart, PHP_ROUND_HALF_DOWN)
        ));

        return 0;
    }
}
