<?php

namespace Tests\Entities;

use App\Entities\Curso;
use App\Enums\Temas;
use PHPUnit\Framework\TestCase;

class CursoTest extends TestCase
{
    private function make(): Curso
    {
        return new Curso('PHP Avançado', 'Aprenda PHP', Temas::Tecnologia, 'https://img.jpg');
    }

    public function test_id_is_null_by_default(): void
    {
        $this->assertNull($this->make()->getId());
    }

    public function test_set_and_get_id(): void
    {
        $curso = $this->make();
        $curso->setId(42);
        $this->assertSame(42, $curso->getId());
    }

    public function test_getters_return_constructor_values(): void
    {
        $curso = $this->make();
        $this->assertSame('PHP Avançado', $curso->getTitulo());
        $this->assertSame('Aprenda PHP', $curso->getDescricao());
        $this->assertSame(Temas::Tecnologia, $curso->getTema());
        $this->assertSame('https://img.jpg', $curso->getUrlImagem());
    }
}
