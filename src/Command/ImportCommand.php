<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Command;

use ACSEO\TypesenseBundle\DataProvider\DataProvider;
use ACSEO\TypesenseBundle\Manager\CollectionManager;
use ACSEO\TypesenseBundle\Manager\DocumentManager;
use ACSEO\TypesenseBundle\Transformer\Transformer;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
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
    private $dataProvider;
    private const ACTIONS = [
        'create',
        'upsert',
        'update',
    ];
    private $isError = false;

    public function __construct(
        EntityManagerInterface $em,
        CollectionManager $collectionManager,
        DocumentManager $documentManager,
        Transformer $transformer,
        DataProvider $dataProvider
    ) {
        parent::__construct();
        $this->em                = $em;
        $this->collectionManager = $collectionManager;
        $this->documentManager   = $documentManager;
        $this->transformer       = $transformer;
        $this->dataProvider      = $dataProvider;
    }

    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Import collections from Database')
            ->addOption('action', null, InputOption::VALUE_OPTIONAL, 'Action modes for typesense import ("create", "upsert" or "update")', 'upsert')
            ->addOption('indexes', null, InputOption::VALUE_OPTIONAL, 'The index(es) to repopulate. Comma separated values')
            ->addOption('first-page', null, InputOption::VALUE_REQUIRED, 'The pager\'s page to start population from. Including the given page.', 1)
            ->addOption('last-page', null, InputOption::VALUE_REQUIRED, 'The pager\'s page to end population on. Including the given page.', null)
            ->addOption('max-per-page', null, InputOption::VALUE_REQUIRED, 'The pager\'s page size', 100)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!in_array($input->getOption('action'), self::ACTIONS, true)) {
            $io->error('Action option only takes the values : "create", "upsert" or "update"');

            return 1;
        }

        // 'setMiddlewares' method only exists for Doctrine version >=3.0.0
        if (method_exists($this->em->getConnection()->getConfiguration(), 'setMiddlewares')) {
            $this->em->getConnection()->getConfiguration()->setMiddlewares(
                [new \Doctrine\DBAL\Logging\Middleware(new \Psr\Log\NullLogger())]
            );
        } else {
            // keep compatibility with versions 2.x.x of Doctrine
            $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        }

        $execStart = microtime(true);
        $populated = 0;

        $io->newLine();

        $collectionDefinitions = $this->collectionManager->getCollectionDefinitions();

        $indexes = (null !== $indexes = $input->getOption('indexes')) ? explode(',', $indexes) : \array_keys($collectionDefinitions);
        foreach ($indexes as $index) {
            if (!isset($collectionDefinitions[$index])) {
                $io->error('Unable to find index "'.$index.'" in collection definition (available : '.implode(', ', array_keys($collectionDefinitions)).')');

                return 2;
            }
        }

        foreach ($indexes as $index) {
            try {
                $populated += $this->populateIndex($input, $output, $index);
            } catch (\Throwable $e) {
                $this->isError = true;
                $io->error($e->getMessage());

                return 2;
            }
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

    private function populateIndex(InputInterface $input, OutputInterface $output, string $index)
    {
        $populated = 0;
        $io        = new SymfonyStyle($input, $output);

        $collectionDefinitions = $this->collectionManager->getCollectionDefinitions();
        $collectionDefinition  = $collectionDefinitions[$index];
        $action                = $input->getOption('action');

        $firstPage  = (int) $input->getOption('first-page');
        $maxPerPage = (int) $input->getOption('max-per-page');

        $collectionName = $collectionDefinition['typesense_name'];
        $class          = $collectionDefinition['entity'];

        $nbEntities = (int) $this->em->createQuery('select COUNT(u.id) from '.$class.' u')->getSingleScalarResult();

        $nbPages = ceil($nbEntities / $maxPerPage);
        
        if ($input->getOption('last-page')) {
            $lastPage = $input->getOption('last-page');
            if ($lastPage > $nbPages) {
                throw new \Exception('The last-page option ('.$lastPage.') is bigger than the number of pages ('.$nbPages.')');
            }
        } else {
            $lastPage = $nbPages;
        }

        if ($lastPage < $firstPage) {
            throw new \Exception('The first-page option ('.$firstPage.') is bigger than the last-page option ('.$lastPage.')');
        }

        $io->text('<info>['.$collectionName.'] '.$class.'</info> '.$nbEntities.' entries to insert splited into '.$nbPages.' pages of '.$maxPerPage.' elements. Insertion from page '.$firstPage.' to '.$lastPage.'.');

        $entityClass = ClassUtils::getRealClass($class);

        for ($i = $firstPage; $i <= $lastPage; ++$i) {
            $elements = $this->dataProvider->getData($class, $i, $maxPerPage);

            $data = [];
            foreach ($elements as $element) {
                $data[] = $this->transformer->convert($element, $entityClass);
            }

            $io->text('Import <info>['.$collectionName.'] '.$class.'</info> Page '.$i.' of '.$lastPage.' ('.count($data).' items)');

            $result = $this->documentManager->import($collectionName, $data, $action);

            if ($this->printErrors($io, $result)) {
                $this->isError = true;

                throw new \Exception('Error happened during the import of the collection : '.$collectionName.' (you can see them with the option -v)');
            }

            $populated += count($data);
        }

        $io->newLine();

        return $populated;
    }

    private function printErrors(SymfonyStyle $io, array $result): bool
    {
        $isError = false;
        foreach ($result as $item) {
            if (!$item['success']) {
                $isError = true;
                $io->error($item['error']);
            }
        }

        return $isError;
    }
}
