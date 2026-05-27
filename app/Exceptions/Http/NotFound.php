<?php

namespace App\Exceptions\Http;

use App\Contracts\HttpExceptionInterface;

class NotFound extends \RuntimeException implements HttpExceptionInterface
{
    public function __construct(string $message = 'Not Found')
    {
        parent::__construct($message, 404);
    }

    public function getStatusCode(): int
    {
        return 404;
    }
}
