<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Transformer;

abstract class AbstractTransformer
{
    public const TYPE_COLLECTION   = 'collection';
    public const TYPE_DATETIME     = 'datetime';
    public const TYPE_PRIMARY      = 'primary';
    public const TYPE_OBJECT       = 'object';
    public const TYPE_ARRAY_STRING = 'string[]';
    public const TYPE_STRING       = 'string';
    public const TYPE_INT_32       = 'int32';
    public const TYPE_INT_64       = 'int64';

    /**
     * Convert an object to a array of data indexable by typesense.
     *
     * @param object $entity the object to convert
     *
     * @return array the converted data
     */
    abstract public function convert(object $entity): array;

    /**
     * Convert a value to an acceptable value for typesense.
     *
     * @param string $objectClass the object class name
     * @param string $properyName the property of the object
     * @param [type] $value the value to convert
     */
    abstract public function castValue(string $objectClass, string $properyName, $value);

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

        return $type;
    }
}
