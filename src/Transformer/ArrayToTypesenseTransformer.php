<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Transformer;

use App\Entity\Subnet;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\PropertyAccess\Exception\RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ArrayToTypesenseTransformer extends AbstractTransformer implements Transformer
{
    public function __construct(array $collectionDefinitions)
    {
        $this->collectionDefinitions = $collectionDefinitions;

        $this->entityToCollectionMapping = [];
        foreach ($this->collectionDefinitions as $collection => $collectionDefinition) {
            $this->entityToCollectionMapping[$collectionDefinition['entity']] = $collection;
        }
    }

    public function convert($element,string $className = null): array
    {
        if(!is_array($element)){
            throw new \Exception(sprintf('Data must be an array'));
        }

        foreach ($this->entityToCollectionMapping as $class => $collection) {
            if (is_a($className, $class, true)) {
                $className = $class;
                break;
            }
        }

        if (!isset($this->entityToCollectionMapping[$className])) {
            throw new \Exception(sprintf('Class %s is not supported for Doctrine To Typesense Transformation', $className));
        }

        $data = [];

        $fields = $this->collectionDefinitions[$this->entityToCollectionMapping[$className]]['fields'];

        foreach ($fields as $fieldsInfo) {
            try {
                $value = $element[$fieldsInfo['entity_attribute']];
            } catch (RuntimeException $exception) {
                $value = null;
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
}
