<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Finder;

use ACSEO\TypesenseBundle\Client\CollectionClient;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class CollectionFinder implements CollectionFinderInterface
{
    private $collectionConfig;
    private $collectionClient;
    private $em;

    public function __construct(CollectionClient $collectionClient, EntityManagerInterface $em, array $collectionConfig)
    {
        $this->collectionConfig = $collectionConfig;
        $this->collectionClient = $collectionClient;
        $this->em               = $em;
    }

    public function rawQuery(TypesenseQuery $query)
    {
        return $this->search($query);
    }

    public function query(TypesenseQuery $query)
    {
        $results = $this->search($query);

        return $this->hydrate($results);
    }

    private function hydrate($results)
    {
        $ids             = [];
        $primaryKeyInfos = $this->getPrimaryKeyInfo();
        foreach ($results->getResults() as $result) {
            $ids[] = $result['document'][$primaryKeyInfos['documentAttribute']];
        }

        $hydratedResults = [];
        if (count($ids)) {
            $dql = sprintf(
                'SELECT e FROM %s e WHERE e.%s IN (:ids)',
                $this->collectionConfig['entity'],
                $primaryKeyInfos['entityAttribute']
            );

            $query = $this->em->createQuery($dql);
            $query->setParameter('ids', $ids);

            $unorderedResults = $query->getResult();

            // sort index
            $idIndex = array_flip($ids);

            usort($unorderedResults, function ($a, $b) use ($idIndex, $primaryKeyInfos) {
                $entityIdMethod = 'get' . ucfirst($primaryKeyInfos['entityAttribute']);
                $idA = $a->$entityIdMethod();
                $idB = $b->$entityIdMethod();

                return $idIndex[$idA] <=> $idIndex[$idB];
            });

            $hydratedResults = $unorderedResults;

        }
        $results->setHydratedHits($hydratedResults);
        $results->setHydrated(true);

        return $results;
    }

    private function search(TypesenseQuery $query)
    {
        $result = $this->collectionClient->search($this->collectionConfig['typesense_name'], $query);

        return new TypesenseResponse($result);
    }

    private function getPrimaryKeyInfo()
    {
        foreach ($this->collectionConfig['fields'] as $name => $config) {
            if ($config['type'] === 'primary') {
                return ['entityAttribute' => $config['entity_attribute'], 'documentAttribute' => $config['name']];
            }
        }

        throw new \Exception(sprintf('Primary key info have not been found for Typesense collection %s', $this->collectionConfig['typesense_name']));
    }
}
