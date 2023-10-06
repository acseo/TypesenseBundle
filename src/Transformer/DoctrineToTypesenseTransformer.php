<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Transformer;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\Exception\RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DoctrineToTypesenseTransformer extends AbstractTransformer
{
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

    public function convert($element, string $className): array
    {
        $entityClass = ClassUtils::getClass($entity);

        // See : https://github.com/acseo/TypesenseBundle/pull/91
        // Allow subclasses to be recognized as a parent class
        foreach (array_keys($this->entityToCollectionMapping) as $class) {
            if (is_a($entityClass, $class, true)) {
                $entityClass = $class;
                break;
            }
        }
        

        if (!isset($this->entityToCollectionMapping[$entityClass])) {
            throw new \Exception(sprintf('Class %s is not supported for Doctrine To Typesense Transformation', $entityClass));
        }

        $data = [];

        $fields = $this->collectionDefinitions[$this->entityToCollectionMapping[$className]]['fields'];

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
                $className,
                $name,
                $value
            );
        }

        return $data;
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
