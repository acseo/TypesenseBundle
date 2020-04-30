<?php

namespace ACSEO\TypesenseBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use ACSEO\TypesenseBundle\Manager\CollectionManager;
use ACSEO\TypesenseBundle\Manager\DocumentManager;
use ACSEO\TypesenseBundle\Transformer\DoctrineToTypesenseTransformer;

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
        $entityClassname = get_class($entity);

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
        $entityClassname = get_class($entity);

        if (!in_array($entityClassname, array_values($this->managedClassNames))) {
            return;
        }

        $collection = array_search($entityClassname, $this->managedClassNames);
        $data = $this->transformer->convert($entity);
        $this->documentManager->delete($collection, $data['id']);
        $this->documentManager->index($collection, $data);
    }


    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        $entityClassname = get_class($entity);

        if (!in_array($entityClassname, array_values($this->managedClassNames))) {
            return;
        }

        $collection = array_search($entityClassname, $this->managedClassNames);
        $data = $this->transformer->convert($entity);
        $this->documentManager->delete($collection, $data['id']);
    }
}