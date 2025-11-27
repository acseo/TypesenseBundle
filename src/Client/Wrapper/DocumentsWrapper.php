<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Client\Wrapper;

use ACSEO\TypesenseBundle\Logger\QueryLoggerInterface;
use Typesense\Documents;
use Webmozart\Assert\Assert;

class DocumentsWrapper implements \ArrayAccess
{
    private Documents $documents;
    private ?QueryLoggerInterface $logger;
    private string $collectionName;

    public function __construct(Documents $documents, string $collectionName, ?QueryLoggerInterface $logger)
    {
        Assert::stringNotEmpty($collectionName, 'Collection name must be a non-empty string');

        $this->documents = $documents;
        $this->collectionName = $collectionName;
        $this->logger = $logger;
    }

    public function search(array $params)
    {
        $startTime = microtime(true);
        $error = null;

        try {
            $result = $this->documents->search($params);

            if ($this->logger) {
                $duration = microtime(true) - $startTime;
                $this->logger->logQuery($this->collectionName, 'search', $params, $duration, null, $result);
            }

            return $result;
        } catch (\Exception $e) {
            $error = $e->getMessage();

            if ($this->logger) {
                $duration = microtime(true) - $startTime;
                $this->logger->logQuery($this->collectionName, 'search', $params, $duration, $error, null);
            }

            throw $e;
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->documents[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->documents[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->documents[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->documents[$offset]);
    }

    public function __call($name, $arguments)
    {
        return $this->documents->{$name}(...$arguments);
    }

    public function __get($name)
    {
        return $this->documents->{$name};
    }
}
