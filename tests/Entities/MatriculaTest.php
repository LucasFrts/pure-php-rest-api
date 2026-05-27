<?php

namespace Tests\Entities;

use App\Entities\Matricula;
use App\Enums\StatusMatricula;
use PHPUnit\Framework\TestCase;

class MatriculaTest extends TestCase
{
    public function test_id_is_null_by_default(): void
    {
        $m = new Matricula(1, 2, 3);
        $this->assertNull($m->getId());
    }

    public function test_set_and_get_id(): void
    {
        $m = new Matricula(1, 2, 3);
        $m->setId(10);
        $this->assertSame(10, $m->getId());
    }

    public function test_getters(): void
    {
        $m = new Matricula(usuarioId: 1, turmaId: 2, cursoId: 3);
        $this->assertSame(1, $m->getUsuarioId());
        $this->assertSame(2, $m->getTurmaId());
        $this->assertSame(3, $m->getCursoId());
    }

    public function test_status_defaults_to_ativo(): void
    {
        $m = new Matricula(1, 2, 3);
        $this->assertSame(StatusMatricula::Ativo, $m->getStatus());
    }

    public function test_constructor_accepts_explicit_status(): void
    {
        $m = new Matricula(1, 2, 3, StatusMatricula::Cancelado);
        $this->assertSame(StatusMatricula::Cancelado, $m->getStatus());
    }

    public function test_set_status(): void
    {
        $m = new Matricula(1, 2, 3);
        $m->setStatus(StatusMatricula::Cancelado);
        $this->assertSame(StatusMatricula::Cancelado, $m->getStatus());
    }
}
