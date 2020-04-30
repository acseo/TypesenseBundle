<?php

namespace ACSEO\TypesenseBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Console\Input\InputOption;
use ACSEO\TypesenseBundle\Client\TypesenseClient;
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
            ->setDescription('Populates collections from Database')
        ;
    }    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $io->progressStart();

        $collectionDefinitions = $this->collectionManager->getCollectionDefinitions();
        foreach ($collectionDefinitions as $collection => $collectionDefinition)
        {
            $methodCalls = [];
            foreach($collectionDefinition['fields'] as $entityAttribute => $definition) {
                $methodCalls[$definition['name']] = ['entityAttribute' => $entityAttribute, 'entityMethod' => 'get'.ucfirst($entityAttribute)];
            }

            $repository = $this->em->getRepository($collectionDefinition['entity']);
            $entities = $repository->findAll();
            foreach($entities as $entity)
            {
                $data = $this->transformer->convert($entity);
                try {
                    $this->documentManager->delete($collection, $data['id']);
                }
                catch (\Exception $e)
                {
                    // Silence is gold
                }
                
                $this->documentManager->index($collection, $data);
                $io->progressAdvance();
            }
        }

        $io->progressFinish();
        $io->success('Done !');
        return 0;
    }
}