<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Client\Wrapper;

use ACSEO\TypesenseBundle\Logger\QueryLoggerInterface;
use Typesense\Collections;

class CollectionsWrapper implements \ArrayAccess
{
    private Collections $collections;
    private ?QueryLoggerInterface $logger;

    public function __construct(Collections $collections, ?QueryLoggerInterface $logger)
    {
        $this->collections = $collections;
        $this->logger = $logger;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->collections[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        $collection = $this->collections[$offset];
        return new CollectionWrapper($collection, $offset, $this->logger);
    }

    public function offsetSet($offset, $value): void
    {
        $this->collections[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->collections[$offset]);
    }

    public function __call($name, $arguments)
    {
        return $this->collections->{$name}(...$arguments);
    }

    public function __get($name)
    {
        return $this->collections->{$name};
    }
}
