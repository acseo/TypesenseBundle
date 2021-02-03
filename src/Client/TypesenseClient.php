<?php

namespace ACSEO\TypesenseBundle\Client;

use ACSEO\TypesenseBundle\Exception\TypesenseException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Typesense\Client;

class TypesenseClient extends Client
{
    public function __construct(string $url, string $apiKey)
    {
        $urlParsed = parse_url($url);
        
        parent::__construct([
            'nodes'        => [
                [
                    'host'     => $urlParsed['host'],
                    'port'     => $urlParsed['port'],
                    'protocol' => $urlParsed['scheme'],
                ],
            ],
            'api_key'      => $apiKey,
            'connection_timeout_seconds' => 5,
        ]);
    }

}
