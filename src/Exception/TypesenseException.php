<?php

declare(strict_types=1);

namespace ACSEO\TypesenseBundle\Exception;

use Symfony\Contracts\HttpClient\ResponseInterface;

final class TypesenseException extends \RuntimeException
{
    public $status;

    /**
     * @var string
     */
    public $message;

    public function __construct(ResponseInterface $response)
    {
        $this->status  = $response->getStatusCode();
        $this->message = json_decode($response->getContent(false), true)['message'] ?? '';
    }
}
