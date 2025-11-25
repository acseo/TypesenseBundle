<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Client;

use ACSEO\TypesenseBundle\Client\Wrapper\CollectionsWrapper;
use ACSEO\TypesenseBundle\Client\Wrapper\MultiSearchWrapper;
use ACSEO\TypesenseBundle\Logger\QueryLoggerInterface;
use Typesense\Aliases;
use Typesense\Client;
use Typesense\Collections;
use Typesense\Debug;
use Typesense\Health;
use Typesense\Keys;
use Typesense\Metrics;
use Typesense\MultiSearch;
use Typesense\Operations;

class TypesenseClient
{
    private $client;
    private $logger;
    private $baseUrl;

    public function __construct(string $url, string $apiKey, ?QueryLoggerInterface $logger = null)
    {
        $this->logger = $logger;

        if ($url === 'null') {
            return;
        }

        $this->baseUrl = $url;
        $urlParsed = parse_url($url);

        $this->client = new Client([
            'nodes' => [
                [
                    'host'     => $urlParsed['host'],
                    'port'     => $urlParsed['port'],
                    'protocol' => $urlParsed['scheme'],
                ],
            ],
            'api_key'                    => $apiKey,
            'connection_timeout_seconds' => 5,
        ]);
    }

    public function getCollections(): ?CollectionsWrapper
    {
        if (!$this->client) {
            return null;
        }

        return new CollectionsWrapper($this->client->collections, $this->logger);
    }

    public function getAliases(): ?Aliases
    {
        if (!$this->client) {
            return null;
        }

        return $this->client->aliases;
    }

    public function getKeys(): ?Keys
    {
        if (!$this->client) {
            return null;
        }

        return $this->client->keys;
    }

    public function getDebug(): ?Debug
    {
        if (!$this->client) {
            return null;
        }

        return $this->client->debug;
    }

    public function getMetrics(): ?Metrics
    {
        if (!$this->client) {
            return null;
        }

        return $this->client->metrics;
    }

    public function getHealth(): ?Health
    {
        if (!$this->client) {
            return null;
        }

        return $this->client->health;
    }

    public function getOperations(): ?Operations
    {
        if (!$this->client) {
            return null;
        }

        return $this->client->operations;
    }

    public function getMultiSearch(): ?MultiSearchWrapper
    {
        if (!$this->client) {
            return null;
        }

        return new MultiSearchWrapper($this->client->multiSearch, $this->logger);
    }

    /**
     * This allow to be use to use new Typesense\Client functions
     * before we update this client.
     */
    public function __call($name, $arguments)
    {
        if (!$this->client) {
            return null;
        }

        return $this->client->{$name}(...$arguments);
    }

    public function __get($name)
    {
        if (!$this->client) {
            return null;
        }

        $value = $this->client->{$name};

        if ($name === 'collections') {
            return new CollectionsWrapper($value, $this->logger);
        }

        if ($name === 'multiSearch') {
            return new MultiSearchWrapper($value, $this->logger);
        }

        return $value;
    }

    public function isOperationnal(): bool
    {
        return $this->client !== null;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function getLogger(): ?QueryLoggerInterface
    {
        return $this->logger;
    }
}
