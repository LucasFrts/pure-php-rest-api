<?php

namespace App\Exceptions\Http;

use App\Contracts\HttpExceptionInterface;
use Exception;
use Throwable;

class UnprocessableEntity extends Exception implements HttpExceptionInterface
{
    public function __construct(
        string $message = 'Unprocessable Entity',
        Throwable|null $previous = null
    ) {
        parent::__construct($message, 422, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }
}
