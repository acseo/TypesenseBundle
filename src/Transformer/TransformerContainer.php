<?php

namespace ACSEO\TypesenseBundle\Transformer;

class TransformerContainer implements Transformer
{
    /**
     * @var iterable<ContextAwareTransformer> $transformers
     */
    private iterable $transformers;

    /**
     * @param iterable<ContextAwareTransformer> $transformers
     */
    public function __construct(iterable $transformers)
    {
        $this->transformers = $transformers;
    }

    public function convert($element, string $className): array
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($element, $className)) {
                return $transformer->convert($element, $className);
            }
        }

        throw new \Exception(sprintf('No transformer found for class %s', $className));
    }
}
