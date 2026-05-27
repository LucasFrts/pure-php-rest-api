<?php

namespace Tests\Repositories;

use App\Entities\StatusTurma;
use App\Entities\Turma;
use App\Exceptions\Http\NotFound;
use App\Repositories\TurmaRepository;
use DateTime;
use PDO;
use PHPUnit\Framework\TestCase;

class TurmaRepositoryTest extends TestCase
{
    private PDO $pdo;
    private TurmaRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE turmas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            curso_id INTEGER NOT NULL,
            titulo TEXT NOT NULL,
            descricao TEXT NOT NULL,
            quantidade_vagas INTEGER NOT NULL,
            status TEXT NOT NULL,
            data_inicio TEXT NOT NULL,
            data_fim TEXT NOT NULL
        )');
        $this->repo = new TurmaRepository($this->pdo);
    }

    private function makeTurma(int $cursoId = 1): Turma
    {
        return new Turma(
            'Turma A',
            'Desc',
            30,
            StatusTurma::Disponivel,
            new DateTime('2026-06-01'),
            new DateTime('2026-12-01'),
            $cursoId
        );
    }

    public function test_store_returns_turma_with_id(): void
    {
        $turma = $this->repo->store($this->makeTurma());
        $this->assertInstanceOf(Turma::class, $turma);
        $this->assertNotNull($turma->getId());
        $this->assertSame('Turma A', $turma->getTitulo());
        $this->assertSame(StatusTurma::Disponivel, $turma->getStatus());
    }

    public function test_find_returns_stored_turma(): void
    {
        $stored = $this->repo->store($this->makeTurma());
        $found  = $this->repo->find($stored->getId());
        $this->assertSame($stored->getId(), $found->getId());
    }

    public function test_find_throws_for_unknown_id(): void
    {
        $this->expectException(NotFound::class);
        $this->repo->find(999);
    }

    public function test_update_changes_status(): void
    {
        $turma   = $this->repo->store($this->makeTurma());
        $updated = $this->repo->update(
            $turma->getId(),
            new Turma('Turma A', 'Desc', 30, StatusTurma::Encerrado, new DateTime('2026-06-01'), new DateTime('2026-12-01'), 1)
        );
        $this->assertSame(StatusTurma::Encerrado, $updated->getStatus());
    }

    public function test_destroy_removes_record(): void
    {
        $turma = $this->repo->store($this->makeTurma());
        $this->repo->destroy($turma->getId());
        $this->expectException(NotFound::class);
        $this->repo->find($turma->getId());
    }

    public function test_find_by_curso_id_returns_matching_turmas(): void
    {
        $this->repo->store($this->makeTurma(1));
        $this->repo->store($this->makeTurma(1));
        $this->repo->store($this->makeTurma(2));
        $results = $this->repo->findByCursoId(1);
        $this->assertCount(2, $results);
        foreach ($results as $t) {
            $this->assertSame(1, $t->getCursoId());
        }
    }
}
