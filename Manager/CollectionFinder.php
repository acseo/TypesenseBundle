<?php

namespace ACSEO\TypesenseBundle\Manager;

use ACSEO\TypesenseBundle\Client\CollectionClient;
use Doctrine\ORM\EntityManagerInterface;

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
        foreach ($results->getResults() as $result) {
            $ids[] = $result['document']['id'];
        }
        $hydratedResults = $this->em->getRepository('App\Entity\Book')->findBy(['id' => $ids]);
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
}
