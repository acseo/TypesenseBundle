<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Transformer;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\Exception\RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DoctrineToTypesenseTransformer extends AbstractTransformer
{
    private $collectionDefinitions;
    private $entityToCollectionMapping;
    private $accessor;
    private $container;

    public function __construct(array $collectionDefinitions, PropertyAccessorInterface $accessor, ContainerInterface $container)
    {
        $this->collectionDefinitions = $collectionDefinitions;
        $this->accessor              = $accessor;
        $this->container              = $container;

        $this->entityToCollectionMapping = [];
        foreach ($this->collectionDefinitions as $collection => $collectionDefinition) {
            $this->entityToCollectionMapping[$collectionDefinition['entity']] = $collection;
        }
    }

    public function convert($entity): array
    {
        $entityClass = ClassUtils::getClass($entity);

        if (!isset($this->entityToCollectionMapping[$entityClass])) {
            throw new \Exception(sprintf('Class %s is not supported for Doctrine To Typesense Transformation', $entityClass));
        }

        $data = [];

        $fields = $this->collectionDefinitions[$this->entityToCollectionMapping[$entityClass]]['fields'];

        foreach ($fields as $fieldsInfo) {
            $entityAttribute = $fieldsInfo['entity_attribute'];

            if (str_contains($entityAttribute, '::')) {
                $value = $this->getFieldValueFromService($entity, $entityAttribute);
            } else {
                try {
                    $value = $this->accessor->getValue($entity, $fieldsInfo['entity_attribute']);
                } catch (RuntimeException $exception) {
                    $value = null;
                }
            }

            $name = $fieldsInfo['name'];

            $data[$name] = $this->castValue(
                $entityClass,
                $name,
                $value
            );
        }

        return $data;
    }

    public function castValue(string $entityClass, string $propertyName, $value)
    {
        $collection = $this->entityToCollectionMapping[$entityClass];
        $key        = array_search(
            $propertyName,
            array_column(
                $this->collectionDefinitions[$collection]['fields'],
                'name'
            ), true
        );
        $collectionFieldsDefinitions = array_values($this->collectionDefinitions[$collection]['fields']);
        $originalType                = $collectionFieldsDefinitions[$key]['type'];
        $castedType                  = $this->castType($originalType);

        switch ($originalType.$castedType) {
            case self::TYPE_DATETIME.self::TYPE_INT_64:
                if ($value instanceof \DateTimeInterface) {
                    return $value->getTimestamp();
                }

                return null;
            case self::TYPE_OBJECT.self::TYPE_STRING:
                return $value->__toString();
            case self::TYPE_COLLECTION.self::TYPE_ARRAY_STRING:
                return array_values(
                    $value->map(function ($v) {
                        return $v->__toString();
                    })->toArray()
                );
            case self::TYPE_STRING.self::TYPE_STRING:
            case self::TYPE_PRIMARY.self::TYPE_STRING:
                return (string) $value;
            default:
                return $value;
        }
    }

    private function getFieldValueFromService($entity, $entityAttribute)
    {
        $values = explode('::', $entityAttribute);

        if (count($values) === 2) {
            if ($this->container->has($values[0])) {
                $service = $this->container->get($values[0]);
                return call_user_func(array($service, $values[1]), $entity);
            }
        }

        return null;
    }

}
