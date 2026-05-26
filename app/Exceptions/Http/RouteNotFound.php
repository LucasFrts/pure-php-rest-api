<?php

namespace App\Exceptions\Http;

use App\Contracts\HttpExceptionInterface;
use Exception;
use Throwable;

class RouteNotFound extends Exception implements HttpExceptionInterface
{
    public function __construct(
        string $message = 'Not Found',
        Throwable|null $previous = null
    ) {
        parent::__construct($message, 404, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }
}