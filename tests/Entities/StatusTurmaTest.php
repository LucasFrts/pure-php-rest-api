<?php

namespace Tests\Entities;

use App\Enums\StatusTurma;
use PHPUnit\Framework\TestCase;

class StatusTurmaTest extends TestCase
{
    public function test_from_string_disponivel(): void
    {
        $this->assertSame(StatusTurma::Disponivel, StatusTurma::fromString('disponivel'));
    }

    public function test_from_string_encerrado(): void
    {
        $this->assertSame(StatusTurma::Encerrado, StatusTurma::fromString('encerrado'));
    }

    public function test_from_string_invalid_throws(): void
    {
        $this->expectException(\ValueError::class);
        StatusTurma::fromString('outro');
    }

    public function test_to_string_disponivel(): void
    {
        $this->assertSame('disponivel', StatusTurma::Disponivel->toString());
    }

    public function test_to_string_encerrado(): void
    {
        $this->assertSame('encerrado', StatusTurma::Encerrado->toString());
    }
}
