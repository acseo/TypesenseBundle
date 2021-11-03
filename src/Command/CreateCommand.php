<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Command;

use ACSEO\TypesenseBundle\Manager\CollectionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command
{
    protected static $defaultName = 'typesense:create';
    private $collectionManager;

    public function __construct(CollectionManager $collectionManager)
    {
        parent::__construct();
        $this->collectionManager = $collectionManager;
    }

    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Create Typsenses indexes')

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $defs = $this->collectionManager->getCollectionDefinitions();

        foreach ($defs as $name => $def) {
            try {
                $output->writeln(sprintf('<info>Deleting</info> <comment>%s</comment>', $name));
                $this->collectionManager->deleteCollextion($name);
            } catch (\Typesense\Exceptions\ObjectNotFound $exception) {
                $output->writeln(sprintf('<comment>%s</comment> <info>does not exists</info> ', $name));
            }

            $output->writeln(sprintf('<info>Creating</info> <comment>%s</comment>', $name));
            $this->collectionManager->createCollection($name);
        }

        return 0;
    }
}
