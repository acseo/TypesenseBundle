<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Client\Wrapper;

use ACSEO\TypesenseBundle\Logger\QueryLoggerInterface;
use Typesense\Collection;
use Webmozart\Assert\Assert;

class CollectionWrapper
{
    private Collection $collection;
    private ?QueryLoggerInterface $logger;
    private string $collectionName;

    public function __construct(Collection $collection, string $collectionName, ?QueryLoggerInterface $logger)
    {
        Assert::stringNotEmpty($collectionName, 'Collection name must be a non-empty string');

        $this->collection = $collection;
        $this->collectionName = $collectionName;
        $this->logger = $logger;
    }

    public function __call($name, $arguments)
    {
        return $this->collection->{$name}(...$arguments);
    }

    public function __get($name)
    {
        $value = $this->collection->{$name};

        if ($name === 'documents') {
            return new DocumentsWrapper($value, $this->collectionName, $this->logger);
        }

        return $value;
    }
}
