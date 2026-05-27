<?php

namespace Tests\Entities;

use App\Enums\StatusTurma;
use App\Entities\Turma;
use DateTime;
use PHPUnit\Framework\TestCase;

class TurmaTest extends TestCase
{
    private function make(): Turma
    {
        return new Turma(
            'Turma A',
            'Primeira turma',
            30,
            StatusTurma::Disponivel,
            new DateTime('2026-06-01'),
            new DateTime('2026-12-01'),
            5
        );
    }

    public function test_id_is_null_by_default(): void
    {
        $this->assertNull($this->make()->getId());
    }

    public function test_set_and_get_id(): void
    {
        $turma = $this->make();
        $turma->setId(7);
        $this->assertSame(7, $turma->getId());
    }

    public function test_curso_id_accessible(): void
    {
        $this->assertSame(5, $this->make()->getCursoId());
    }

    public function test_getters_return_constructor_values(): void
    {
        $turma = $this->make();
        $this->assertSame('Turma A', $turma->getTitulo());
        $this->assertSame('Primeira turma', $turma->getDescricao());
        $this->assertSame(30, $turma->getQuantidadeVagas());
        $this->assertSame(StatusTurma::Disponivel, $turma->getStatus());
        $this->assertSame(5, $turma->getCursoId());
    }
}
