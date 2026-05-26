<?php

namespace App\Exceptions\Http;

use Exception;
use Throwable;
use Override;

class NotFound extends Exception 
{
    #[Override]
    public function __construct(string $message = "404 not found", int $code = 404, Throwable|null $previous = null)
    {
        return parent::__construct($message, $code, $previous);
    }   
}