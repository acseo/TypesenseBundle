<?php

namespace ACSEO\TypesenseBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Console\Input\InputOption;
use ACSEO\TypesenseBundle\Manager\CollectionManager;
use ACSEO\TypesenseBundle\Exception\TypesenseException;
use Symfony\Component\HttpFoundation\Response;

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
            ->setName('test:test')
            
            ->setDescription('create')
        ;
    }    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $defs = $this->collectionManager->getCollectionDefinitions();

        foreach ($defs as $name => $def)
        {
            try {
                $output->writeln(sprintf('<info>Deleting</info> <comment>%s</comment>', $name));
                $this->collectionManager->deleteCollextion($name);
            } catch (TypesenseException $exception) {
                if ($exception->status === Response::HTTP_NOT_FOUND && $exception->message === 'Not Found') {
                    $output->writeln(sprintf('<comment>%s</comment> <info>does not exists</info> ', $name));
                }
            }

            $output->writeln(sprintf('<info>Creating</info> <comment>%s</comment>', $name));
            $this->collectionManager->createCollection($name);
        }

        return 0;
    }
}