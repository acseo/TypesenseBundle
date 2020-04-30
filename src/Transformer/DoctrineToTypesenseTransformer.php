<?php

namespace ACSEO\TypesenseBundle\Transformer;

class DoctrineToTypesenseTransformer
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

    private function castValue($entityClass, $name, $value)
    {
        $collection = $this->entityToCollectionMapping[$entityClass];
        $originalType = $this->collectionDefinitions[$collection]['fields'][$name]['type'];
        $castedType = $this->castType($originalType);
        if ($originalType != $castedType) {
            switch ($originalType.$castedType) {
                case 'datetime'.'int32':
                    return $value->getTimestamp();
                case 'primary'.'int32':
                    return (int) $value;
                case 'object'.'string':
                    return $value->__toString();
                case 'collection'.'string[]':
                    return $value->map(function ($v) {
                        return $v->__toString();
                    })->toArray();
                break;
            }
        }
        return $value;
    }

    private function castType($type)
    {
        if ($type == 'collection') {
            return 'string[]';
        }
        if ($type == 'datetime') {
            return 'int32';
        }
        if ($type == 'primary') {
            return 'int32';
        }
        if ($type == 'object') {
            return 'string';
        }

        return $type;
    }
}
