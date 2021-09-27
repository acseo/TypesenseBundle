<?php

namespace ACSEO\TypesenseBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use ACSEO\TypesenseBundle\Manager\CollectionManager;
use ACSEO\TypesenseBundle\Manager\DocumentManager;
use ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer;
use Doctrine\Common\Util\ClassUtils;

class TypesenseIndexer
{
    private $collectionManager;
    private $transformer;
    private $managedClassNames;

    public function __construct(CollectionManager $collectionManager, DocumentManager $documentManager, DoctrineToTypesenseTransformer $transformer)
    {
        $this->collectionManager = $collectionManager;
        $this->documentManager = $documentManager;
        $this->transformer = $transformer;

        $this->managedClassNames = $this->collectionManager->getManagedClassNames();
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $entityClassname = ClassUtils::getClass($entity);

        if (!in_array($entityClassname, array_values($this->managedClassNames))) {
            return;
        }

        $collection = array_search($entityClassname, $this->managedClassNames);
        $data = $this->transformer->convert($entity);
        $this->documentManager->index($collection, $data);
    }


    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $entityClassname = ClassUtils::getClass($entity);
        if (!in_array($entityClassname, array_values($this->managedClassNames))) {
            return;
        }

        $collectionDefinitionKey = array_search($entityClassname, $this->managedClassNames);
        $collectionConfig = $this->collectionManager->getCollectionDefinitions()[$collectionDefinitionKey];


        $primaryKey = $this->getPrimaryKeyInfo($collectionConfig);

        $collection = array_search($entityClassname, $this->managedClassNames);
        $data = $this->transformer->convert($entity);
   
        $this->documentManager->delete($collection, $data['id']);
        $this->documentManager->index($collection, $data);
    }


    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        $entityClassname = ClassUtils::getClass($entity);

        if (!in_array($entityClassname, array_values($this->managedClassNames))) {
            return;
        }

        $collection = array_search($entityClassname, $this->managedClassNames);
        $data = $this->transformer->convert($entity);

        $this->documentManager->delete($collection, $data['id']);
    }


    private function getPrimaryKeyInfo($collectionConfig)
    {
        foreach ($collectionConfig['fields'] as $name => $config) {
            if ($config['type'] == 'primary') {
                return ['entityAttribute' => $name, 'documentAttribute' => $config['name']];
            }
        }
        throw new \Exception(sprintf('Primary key info have not been found for Typesense collection %s', $collectionConfig['typesense_name']));
    }
}
