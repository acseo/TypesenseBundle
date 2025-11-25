<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Client\Wrapper;

use ACSEO\TypesenseBundle\Logger\QueryLoggerInterface;
use Typesense\Documents;
use Webmozart\Assert\Assert;

class DocumentsWrapper
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

    public function __call($name, $arguments)
    {
        return $this->documents->{$name}(...$arguments);
    }

    public function __get($name)
    {
        return $this->documents->{$name};
    }
}
