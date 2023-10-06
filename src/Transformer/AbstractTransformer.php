<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Transformer;

abstract class AbstractTransformer implements Transformer
{
    protected array $entityToCollectionMapping;
    protected array $collectionDefinitions;
    public const TYPE_COLLECTION   = 'collection';
    public const TYPE_DATETIME     = 'datetime';
    public const TYPE_PRIMARY      = 'primary';
    public const TYPE_OBJECT       = 'object';
    public const TYPE_ARRAY_STRING = 'string[]';
    public const TYPE_STRING       = 'string';
    public const TYPE_INT_32       = 'int32';
    public const TYPE_INT_64       = 'int64';
    public const TYPE_BOOL         = 'bool';


    /**
     * map a type to a typesense type field.
     */
    public function castType(string $type): string
    {
        if ($type === self::TYPE_COLLECTION) {
            return self::TYPE_ARRAY_STRING;
        }
        if ($type === self::TYPE_DATETIME) {
            return self::TYPE_INT_64;
        }
        if ($type === self::TYPE_PRIMARY) {
            return self::TYPE_STRING;
        }
        if ($type === self::TYPE_OBJECT) {
            return self::TYPE_STRING;
        }
        if ($type === self::TYPE_BOOL) {
            return self::TYPE_BOOL;
        }

        return $type;
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
}
