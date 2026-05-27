<?php

namespace Tests\Entities;

use App\Entities\Usuario;
use App\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

class UsuarioTest extends TestCase
{
    private function make(): Usuario
    {
        return new Usuario('João Silva', new Email('joao@example.com'));
    }

    public function test_id_is_null_by_default(): void
    {
        $this->assertNull($this->make()->getId());
    }

    public function test_set_and_get_id(): void
    {
        $usuario = $this->make();
        $usuario->setId(3);
        $this->assertSame(3, $usuario->getId());
    }

    public function test_getters_return_constructor_values(): void
    {
        $usuario = $this->make();
        $this->assertSame('João Silva', $usuario->getNome());
        $this->assertSame('joao@example.com', $usuario->getEmail()->getValue());
    }
}
