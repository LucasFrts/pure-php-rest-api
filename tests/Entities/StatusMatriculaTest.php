<?php

namespace Tests\Entities;

use App\Entities\StatusMatricula;
use PHPUnit\Framework\TestCase;

class StatusMatriculaTest extends TestCase
{
    public function test_from_string_ativo(): void
    {
        $this->assertSame(StatusMatricula::Ativo, StatusMatricula::fromString('ativo'));
    }

    public function test_from_string_cancelado(): void
    {
        $this->assertSame(StatusMatricula::Cancelado, StatusMatricula::fromString('cancelado'));
    }

    public function test_from_string_invalid_throws(): void
    {
        $this->expectException(\ValueError::class);
        StatusMatricula::fromString('outro');
    }

    public function test_to_string_ativo(): void
    {
        $this->assertSame('ativo', StatusMatricula::Ativo->toString());
    }

    public function test_to_string_cancelado(): void
    {
        $this->assertSame('cancelado', StatusMatricula::Cancelado->toString());
    }
}
