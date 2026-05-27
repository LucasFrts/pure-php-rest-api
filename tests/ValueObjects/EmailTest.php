<?php

namespace Tests\ValueObjects;

use App\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function test_stores_valid_email(): void
    {
        $email = new Email('user@example.com');
        $this->assertSame('user@example.com', $email->getValue());
    }

    public function test_to_string_returns_address(): void
    {
        $email = new Email('user@example.com');
        $this->assertSame('user@example.com', (string) $email);
    }

    public function test_invalid_email_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('not-an-email');
    }
}
