<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Client\Wrapper;

use ACSEO\TypesenseBundle\Logger\QueryLoggerInterface;
use Typesense\MultiSearch;
use Webmozart\Assert\Assert;

class MultiSearchWrapper
{
    private MultiSearch $multiSearch;
    private ?QueryLoggerInterface $logger;

    public function __construct(MultiSearch $multiSearch, ?QueryLoggerInterface $logger)
    {
        $this->multiSearch = $multiSearch;
        $this->logger = $logger;
    }

    public function perform(array $searchRequests, array $commonParams = [])
    {
        $startTime = microtime(true);
        $error = null;

        try {
            $result = $this->multiSearch->perform($searchRequests, $commonParams);

            if ($this->logger) {
                $duration = microtime(true) - $startTime;
                $collection = $searchRequests['searches'][0]['collection'] ?? null;
                $firstResult = $result['results'][0] ?? null;
                $this->logger->logQuery($collection, 'multi_search', $searchRequests, $duration, null, $firstResult);
            }

            return $result;
        } catch (\Exception $e) {
            $error = $e->getMessage();

            if ($this->logger) {
                $duration = microtime(true) - $startTime;
                $collection = $searchRequests['searches'][0]['collection'] ?? null;
                $this->logger->logQuery($collection, 'multi_search', $searchRequests, $duration, $error, null);
            }

            throw $e;
        }
    }

    public function __call($name, $arguments)
    {
        return $this->multiSearch->{$name}(...$arguments);
    }
}
