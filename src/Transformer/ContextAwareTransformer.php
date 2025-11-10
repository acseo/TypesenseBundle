<?php

namespace ACSEO\TypesenseBundle\Transformer;

interface ContextAwareTransformer extends Transformer
{
    public function supports(mixed $element, string $className = null);
}
