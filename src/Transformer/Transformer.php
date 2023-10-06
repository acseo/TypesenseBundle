<?php

namespace ACSEO\TypesenseBundle\Transformer;

interface Transformer
{
    /**
     * Convert an object to a array of data indexable by typesense.
     * @param object|array $element the data to convert
     * @param string $className the class name of the object
     * @return array the converted data
     */
    public function convert($element, string $className): array;

    /**
     * Convert a value to an acceptable value for typesense.
     *
     * @param string $objectClass the object class name
     * @param string $properyName the property of the object
     * @param [type] $value the value to convert
     */
    public function castValue(string $objectClass, string $properyName, $value);

}