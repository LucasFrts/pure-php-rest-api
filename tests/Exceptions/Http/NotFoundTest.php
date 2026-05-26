<?php

namespace Tests\Exceptions\Http;

use App\Contracts\HttpExceptionInterface;
use App\Exceptions\Http\NotFound;
use PHPUnit\Framework\TestCase;

class NotFoundTest extends TestCase
{
    public function test_implements_http_exception_interface(): void
    {
        $e = new NotFound();
        $this->assertInstanceOf(HttpExceptionInterface::class, $e);
    }

    public function test_get_status_code_returns_404(): void
    {
        $e = new NotFound();
        $this->assertSame(404, $e->getStatusCode());
    }

    public function test_default_message_is_not_found(): void
    {
        $e = new NotFound();
        $this->assertSame('Not Found', $e->getMessage());
    }

    public function test_custom_message_is_preserved(): void
    {
        $e = new NotFound('Resource /users/42 not found');
        $this->assertSame('Resource /users/42 not found', $e->getMessage());
    }
}
