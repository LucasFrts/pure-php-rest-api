<?php

namespace App\Exceptions\Http;

use App\Contracts\HttpExceptionInterface;
use RuntimeException;

/**
 * Erro para requisição mal formatada
 */
class BadRequest extends RuntimeException implements HttpExceptionInterface
{
    public function __construct(string $message = 'Bad Request')
    {
        parent::__construct($message, 400);
    }

    public function getStatusCode(): int
    {
        return 400;
    }
}
