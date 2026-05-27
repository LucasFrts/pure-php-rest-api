<?php

namespace Tests\Repositories;

use App\Entities\Matricula;
use App\Entities\StatusMatricula;
use App\Exceptions\Http\NotFound;
use App\Repositories\MatriculaRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class MatriculaRepositoryTest extends TestCase
{
    private PDO $pdo;
    private MatriculaRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE cursos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            descricao TEXT NOT NULL,
            tema TEXT NOT NULL,
            url_imagem TEXT NOT NULL
        )');
        $this->pdo->exec('CREATE TABLE matriculas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            turma_id INTEGER NOT NULL,
            curso_id INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT \'ativo\',
            UNIQUE(usuario_id, curso_id)
        )');
        $this->pdo->exec("INSERT INTO cursos VALUES (1, 'PHP', 'desc', 'tecnologia', 'img.jpg')");
        $this->repo = new MatriculaRepository($this->pdo);
    }

    public function test_store_returns_matricula_with_id(): void
    {
        $m = $this->repo->store(new Matricula(1, 2, 1));
        $this->assertNotNull($m->getId());
        $this->assertSame(1, $m->getUsuarioId());
        $this->assertSame(2, $m->getTurmaId());
        $this->assertSame(1, $m->getCursoId());
    }

    public function test_find_by_usuario_and_curso_returns_matricula(): void
    {
        $this->repo->store(new Matricula(1, 2, 1));
        $found = $this->repo->findByUsuarioAndCurso(1, 1);
        $this->assertNotNull($found);
        $this->assertSame(1, $found->getUsuarioId());
    }

    public function test_find_by_usuario_and_curso_returns_null_when_not_enrolled(): void
    {
        $result = $this->repo->findByUsuarioAndCurso(1, 1);
        $this->assertNull($result);
    }

    public function test_get_by_usuario_returns_all_matriculas(): void
    {
        $this->pdo->exec("INSERT INTO cursos VALUES (2, 'Marketing', 'desc', 'marketing', 'img.jpg')");
        $this->repo->store(new Matricula(1, 2, 1));
        $this->repo->store(new Matricula(1, 3, 2));
        $results = $this->repo->getByUsuario(1);
        $this->assertCount(2, $results);
    }

    public function test_destroy_removes_matricula(): void
    {
        $m = $this->repo->store(new Matricula(1, 2, 1));
        $this->repo->destroy($m->getId());
        $this->assertNull($this->repo->findByUsuarioAndCurso(1, 1));
    }

    public function test_store_persists_status_and_hydrate_reads_it(): void
    {
        $m = $this->repo->store(new Matricula(1, 2, 1, StatusMatricula::Cancelado));
        $found = $this->repo->findByUsuarioAndCurso(1, 1);
        $this->assertSame(StatusMatricula::Cancelado, $found->getStatus());
    }

    public function test_store_defaults_status_to_ativo(): void
    {
        $this->repo->store(new Matricula(1, 2, 1));
        $found = $this->repo->findByUsuarioAndCurso(1, 1);
        $this->assertSame(StatusMatricula::Ativo, $found->getStatus());
    }

    public function test_find_returns_matricula_by_id(): void
    {
        $stored = $this->repo->store(new Matricula(1, 2, 1));
        $found  = $this->repo->find($stored->getId());
        $this->assertSame($stored->getId(), $found->getId());
        $this->assertSame(1, $found->getUsuarioId());
    }

    public function test_find_throws_not_found_for_unknown_id(): void
    {
        $this->expectException(NotFound::class);
        $this->repo->find(999);
    }

    public function test_update_status_persists_new_status(): void
    {
        $stored   = $this->repo->store(new Matricula(1, 2, 1));
        $updated  = $this->repo->updateStatus($stored->getId(), StatusMatricula::Inativo);
        $this->assertSame(StatusMatricula::Inativo, $updated->getStatus());
        $refetch = $this->repo->find($stored->getId());
        $this->assertSame(StatusMatricula::Inativo, $refetch->getStatus());
    }

    public function test_inativar_by_turma_sets_all_to_inativo(): void
    {
        $this->pdo->exec("INSERT INTO cursos VALUES (2, 'JS', 'desc', 'tecnologia', 'img.jpg')");
        $m1 = $this->repo->store(new Matricula(1, 5, 1));
        $m2 = $this->repo->store(new Matricula(2, 5, 2));
        $this->repo->inativarByTurma(5);
        $this->assertSame(StatusMatricula::Inativo, $this->repo->find($m1->getId())->getStatus());
        $this->assertSame(StatusMatricula::Inativo, $this->repo->find($m2->getId())->getStatus());
    }

    public function test_inativar_by_turma_does_not_affect_other_turmas(): void
    {
        $m = $this->repo->store(new Matricula(1, 10, 1));
        $this->repo->inativarByTurma(99);
        $this->assertSame(StatusMatricula::Ativo, $this->repo->find($m->getId())->getStatus());
    }
}
