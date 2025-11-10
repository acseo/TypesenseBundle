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

}
