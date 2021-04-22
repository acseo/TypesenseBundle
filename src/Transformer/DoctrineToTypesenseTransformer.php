<?php

namespace ACSEO\TypesenseBundle\Transformer;

use Doctrine\Persistence\Proxy;

class DoctrineToTypesenseTransformer extends AbstractTransformer
{
    private $collectionDefinitions;
    private $entityToCollectionMapping;
    private $methodCalls;

    public function __construct(array $collectionDefinitions)
    {
        $this->collectionDefinitions = $collectionDefinitions;
        $this->methodCalls = [];
        $this->entityToCollectionMapping = [];
        foreach ($this->collectionDefinitions as $collection => $collectionDefinition) {
            $this->entityToCollectionMapping[$collectionDefinition['entity']] = $collection;

            $this->methodCalls[$collectionDefinition['entity']] = [];
            foreach ($collectionDefinition['fields'] as $entityAttribute => $definition) {
                $entityAttribute = $definition['entity_attribute'];
                $entityAttributeChain = explode('.', $entityAttribute);
                $methods = [];
                foreach ($entityAttributeChain as $chainableCall) {
                    $methods[] = 'get'.ucfirst($chainableCall);
                }
                $this->methodCalls[$collectionDefinition['entity']][$definition['name']] = ['entityAttribute' => $entityAttribute, 'entityMethods' => $methods];
            }
        }
    }

    public function convert($entity)
    {
        $entityClass = ($entity instanceof Proxy)
        ? get_parent_class($entity)
        : get_class($entity);
        if (!isset($this->methodCalls[$entityClass])) {
            throw new \Exception(sprintf('Class %s is not supported for Doctrine To Typesense Transformation', $entityClass));
        }

        $data = [];
        $methodCalls = $this->methodCalls[$entityClass];

        foreach ($methodCalls as $typesenseField => $callableInfos) {
            $entityMethods = $callableInfos['entityMethods'];
            $value = $entity;
            foreach ($entityMethods as $method) {
                if (null != $value) {
                    $value = $value->{$method}();
                }
            }
            $data[$typesenseField] = $this->castValue(
                $entityClass,
                $typesenseField,
                $value
            );
        }

        return $data;
    }

    public function castValue($entityClass, $name, $value)
    {
        $collection = $this->entityToCollectionMapping[$entityClass];
        $key = array_search($name, array_column(
            $this->collectionDefinitions[$collection]['fields'],
            'name'
        ));
        $collectionFieldsDefinitions = array_values($this->collectionDefinitions[$collection]['fields']);

        $originalType = $collectionFieldsDefinitions[$key]['type'];
        $castedType = $this->castType($originalType);

        switch ($originalType.$castedType) {
            case self::TYPE_DATETIME.self::TYPE_INT_64:
                if ($value instanceof \DateTime) {
                    return $value->getTimestamp();
                }

                return null;
            case self::TYPE_PRIMARY.self::TYPE_STRING:
                return (string) $value;
            case self::TYPE_OBJECT.self::TYPE_STRING:
                return $value->__toString();
            case self::TYPE_COLLECTION.self::TYPE_ARRAY_STRING:
                return array_values($value->map(function ($v) {
                    return $v->__toString();
                })->toArray());
            case self::TYPE_STRING.self::TYPE_STRING:
                return (string) $value;
            default:
                return $value;

            break;
        }
    }
}
