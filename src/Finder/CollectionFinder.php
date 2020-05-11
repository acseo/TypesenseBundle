<?php

namespace ACSEO\TypesenseBundle\Finder;

use ACSEO\TypesenseBundle\Client\CollectionClient;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class CollectionFinder
{
    private $collectionConfig;
    private $collectionClient;
    private $em;

    public function __construct(CollectionClient $collectionClient, EntityManagerInterface $em, array $collectionConfig)
    {
        $this->collectionConfig = $collectionConfig;
        $this->collectionClient = $collectionClient;
        $this->em = $em;
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
        $ids = [];
        $primaryKeyInfos = $this->getPrimaryKeyInfo();
        foreach ($results->getResults() as $result) {
            $ids[] = $result['document'][$primaryKeyInfos['documentAttribute']];
        }
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata($this->collectionConfig['entity'], 'e');
        $query = $this->em->createNativeQuery('SELECT * FROM client WHERE id IN ('.implode(', ', $ids).') ORDER BY FIELD(id,'.implode(', ', $ids).')', $rsm);
        $hydratedResults = $query->getResult();
        $results->setHydratedHits($hydratedResults);
        $results->setHydrated(true);

        return $results;
    }

    private function search(TypesenseQuery $query)
    {
        $result = $this->collectionClient->get(
            sprintf('%s/documents/search?', $this->collectionConfig['typesense_name']) .
            http_build_query($query->getParameters())
        );

        return new TypesenseResponse($result);
    }

    private function getPrimaryKeyInfo()
    {
        foreach ($this->collectionConfig['fields'] as $name => $config) {
            if ($config['type'] == 'primary') {
                return ['entityAttribute' => $name, 'documentAttribute' => $config['name']];
            }
        }
        throw new \Exception(sprintf('Primary key info have not been found for Typesense collection %s', $this->collectionConfig['typesense_name']));
    }
}
