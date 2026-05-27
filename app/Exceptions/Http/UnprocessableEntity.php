<?php

namespace App\Exceptions\Http;

use App\Contracts\HttpExceptionInterface;
use RuntimeException;

class UnprocessableEntity extends RuntimeException implements HttpExceptionInterface
{
    /**
     * @param array<string, mixed> $errors Detalhes estruturados (ex: missing, invalid).
     */
    public function __construct(
        string $message = 'Unprocessable Entity',
        private array $errors = []
    ) {
        parent::__construct($message, 422);
    }

    public function getStatusCode(): int
    {
        return 422;
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
