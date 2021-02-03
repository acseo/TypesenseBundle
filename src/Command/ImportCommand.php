<?php

namespace ACSEO\TypesenseBundle\Command;

use ACSEO\TypesenseBundle\Manager\CollectionManager;
use ACSEO\TypesenseBundle\Manager\DocumentManager;
use ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportCommand extends Command
{
    protected static $defaultName = 'typesense:import';

    private $em;
    private $collectionManager;
    private $documentManager;
    private $transformer;
    private const ACTIONS = [
        'create',
        'upsert',
        'update'
    ];
    private $isError = false;

    public function __construct(
        EntityManagerInterface $em,
        CollectionManager $collectionManager,
        DocumentManager $documentManager,
        DoctrineToTypesenseTransformer $transformer
    )
    {
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
            ->addOption('action', null, InputOption::VALUE_OPTIONAL, 'Action modes for typesense import ("create", "upsert" or "update")', 'upsert');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!in_array($input->getOption('action'), self::ACTIONS)) {
            $io->error('Action option only takes the values : "create", "upsert" or "update"');
            return 1;
        }

        $action = $input->getOption('action');

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $execStart = microtime(true);
        $populated = 0;

        $io->newLine();

        $collectionDefinitions = $this->collectionManager->getCollectionDefinitions();
        foreach ($collectionDefinitions as $collectionDefinition) {
            $collectionName = $collectionDefinition['typesense_name'];
            $class = $collectionDefinition['entity'];

            $q = $this->em->createQuery('select e from ' . $class . ' e');

            $entities = $q->toIterable();

            $nbEntities = (int)$this->em->createQuery('select COUNT(u.id) from ' . $class . ' u')->getSingleScalarResult();
            $populated += $nbEntities;

            $data = [];
            foreach ($entities as $entity) {
                $data[] = $this->transformer->convert($entity);
            }

            $io->text("Import <info>[" . $collectionName . "] " . $class . "</info>");

            $result = $this->documentManager->import($collectionName, $data, $action);

            if ($this->printErrors($io, $result)) {
                $this->isError = true;
                $io->error('Error happened during the import of the collection : ' . $collectionName . ' (you can see them with the option -v)');
                return 2;
            }

            $io->newLine();
        }

        $io->newLine();
        if (!$this->isError) {
            $io->success(sprintf(
                '%s element%s populated in %s seconds',
                $populated,
                $populated > 1 ? 's' : '',
                round(microtime(true) - $execStart, PHP_ROUND_HALF_DOWN)
            ));
        }

        return 0;
    }

    private function printErrors(SymfonyStyle $io, array $result): bool
    {
        $isError = false;
        foreach ($result as $item) {
            if (!$item['success']) {
                $isError = true;
                if ($io->isVerbose()) {
                    $io->error($item['error']);
                }
            }
        }

        return $isError;
    }
}