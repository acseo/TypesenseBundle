<?php

namespace ACSEO\TypesenseBundle\Transformer;

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
        $entityClass = get_class($entity);
        if (!isset($this->methodCalls[$entityClass])) {
            throw new \Exception(sprintf('Class %s is not supported for Doctrine To Typesense Transformation', $entityClass));
        }

        $data = [];
        $methodCalls = $this->methodCalls[$entityClass];

        foreach ($methodCalls as $typesenseField => $callableInfos) {
            $entityMethods = $callableInfos['entityMethods'];
            $value = $entity;
            foreach ($entityMethods as $method) {
                $value = $value->{$method}();
            }
            $data[$typesenseField] = $this->castValue(
                $entityClass,
                $callableInfos['entityAttribute'],
                $value
            );
        }

        return $data;
    }

    public function castValue($entityClass, $name, $value)
    {
        $collection = $this->entityToCollectionMapping[$entityClass];
        $originalType = $this->collectionDefinitions[$collection]['fields'][$name]['type'];
        $castedType = $this->castType($originalType);
        switch ($originalType.$castedType) {
            case self::TYPE_DATETIME.self::TYPE_INT_32:
                if ($value instanceof \DateTime) {
                    return $value->getTimestamp();
                }
                return null;
            case self::TYPE_PRIMARY.self::TYPE_INT_32:
                return (int) $value;
            case self::TYPE_OBJECT.self::TYPE_STRING:
                return $value->__toString();
            case self::TYPE_COLLECTION.self::TYPE_ARRAY_STRING:
                return $value->map(function ($v) {
                    return $v->__toString();
                })->toArray();
            case self::TYPE_STRING.self::TYPE_STRING:
                return (string) $value;
            default:
                return $value;
            break;
        }
    }
}
