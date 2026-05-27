<?php

namespace App\Exceptions\Http;

use App\Contracts\HttpExceptionInterface;

class UnprocessableEntity extends \RuntimeException implements HttpExceptionInterface
{
    public function __construct(string $message = 'Unprocessable Entity')
    {
        parent::__construct($message, 422);
    }

    public function getStatusCode(): int
    {
        return 422;
    }
}
