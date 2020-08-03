<?php

namespace ACSEO\TypesenseBundle\Client;

use ACSEO\TypesenseBundle\Exception\TypesenseException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TypesenseClient
{
    private $host;
    private $apiKey;
    private $client;

    public function __construct(string $host, string $apiKey, HttpClientInterface $client)
    {
        $this->host = $host;
        $this->apiKey = $apiKey;
        $this->client = $client;
    }

    public function get(string $endpoint): array
    {
        return $this->api($endpoint, '', 'GET');
    }

    public function post(string $endpoint, array $data = [], bool $import = false): array
    {
        if ($import) {
            /**
             * @author raphaelstolt in json-lines package
             */
            $lines = [];
            foreach ($data as $line) {
                $lines[] = json_encode($line, JSON_UNESCAPED_UNICODE);
            }

            $data = implode("\n", $lines)
            ."\n";
        } else {
            $data = json_encode($data);
        }

        return $this->api($endpoint, $data, 'POST');
    }

    public function delete(string $endpoint, array $data = []): array
    {
        $data = json_encode($data);

        return $this->api($endpoint, $data, 'DELETE');
    }

    private function api(string $endpoint, string $data = '', string $method = 'POST'): array
    {
        if ('null' === $this->host) {
            return  [];
        }

        $response = $this->client->request($method, "http://{$this->host}/{$endpoint}", [
            'body' => $data,
            'headers' => [
                'X-TYPESENSE-API-KEY' => $this->apiKey,
            ],
        ]);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            return json_decode($response->getContent(), true);
        }

        throw new TypesenseException($response);
    }
}
