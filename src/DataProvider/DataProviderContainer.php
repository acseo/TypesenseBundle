<?php

namespace ACSEO\TypesenseBundle\DataProvider;

class DataProviderContainer implements DataProvider
{
    /**
     * @var iterable<ContextAwareDataProvider> $dataProviders
     */
    private iterable $dataProviders;

    /**
     * @param iterable<ContextAwareDataProvider> $dataProviders
     */
    public function __construct(iterable $dataProviders)
    {
        $this->dataProviders = $dataProviders;
    }

    public function getData(string $className,  int $page, int $maxPerPage): iterable
    {
        foreach ($this->dataProviders as $dataProvider) {
            if ($dataProvider->supports($className)) {
                return $dataProvider->getData($className, $page, $maxPerPage);
            }
        }

        throw new \RuntimeException('No data provider found for '.$className);
    }
}
