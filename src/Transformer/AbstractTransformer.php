<?php

namespace ACSEO\TypesenseBundle\Transformer;

abstract class AbstractTransformer
{
    const TYPE_COLLECTION = 'collection';
    const TYPE_DATETIME = 'datetime';
    const TYPE_PRIMARY = 'primary';
    const TYPE_OBJECT = 'object';
    const TYPE_ARRAY_STRING = 'string[]';
    const TYPE_STRING = 'string';
    const TYPE_INT_32 = 'int32';
    const TYPE_INT_64 = 'int64';

    /**
     * Convert an object to a array of data indexable by typesense
     *
     * @param object $entity the object to convert
     * @return array the converted data
     */
    abstract public function convert(object $entity);

    /**
     * Convert a value to an acceptable value for typesense
     *
     * @param string $objectClass the object class name
     * @param string $properyName the property of the object
     * @param [type] $value the value to convert
     * @return void
     */
    abstract public function castValue(string $objectClass, string $properyName, $value);

    /**
     * map a type to a typesense type field
     *
     * @param string $type
     * @return string
     */
    public function castType(string $type)
    {
        if ($type == self::TYPE_COLLECTION) {
            return self::TYPE_ARRAY_STRING;
        }
        if ($type == self::TYPE_DATETIME) {
            return self::TYPE_INT_32;
        }
        if ($type == self::TYPE_PRIMARY) {
            return self::TYPE_STRING;
        }
        if ($type == self::TYPE_OBJECT) {
            return self::TYPE_STRING;
        }

        return $type;
    }
}
