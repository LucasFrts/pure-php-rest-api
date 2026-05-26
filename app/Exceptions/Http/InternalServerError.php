<?php

namespace App\Exceptions\Http;

use App\Contracts\RuntimeExceptionInterface;
use RuntimeException;
use Throwable;

class InternalServerError extends RuntimeException implements RuntimeExceptionInterface
{
    public function __construct(
        string $message = 'Internal Server Error',
        Throwable|null $previous = null
    ) {
        parent::__construct($message, 500, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }
}