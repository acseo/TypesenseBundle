<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Logger;

interface QueryLoggerInterface
{
    public function logQuery(
        ?string $collection,
        string $operation,
        array $params,
        float $duration,
        ?string $error = null,
        ?array $response = null,
        ?string $method = null,
        ?string $endpoint = null
    ): void;
}
