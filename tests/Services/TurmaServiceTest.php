<?php

namespace Tests\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Entities\Curso;
use App\Entities\StatusTurma;
use App\Entities\Temas;
use App\Entities\Turma;
use App\Exceptions\Http\NotFound;
use App\Services\TurmaService;
use DateTime;
use PHPUnit\Framework\TestCase;

class TurmaServiceTest extends TestCase
{
    private TurmaRepositoryInterface $turmaRepo;
    private CursoRepositoryInterface $cursoRepo;
    private TurmaService $service;

    protected function setUp(): void
    {
        $this->turmaRepo = $this->createMock(TurmaRepositoryInterface::class);
        $this->cursoRepo = $this->createMock(CursoRepositoryInterface::class);
        $this->service   = new TurmaService($this->turmaRepo, $this->cursoRepo);
    }

    private function makeTurma(int $id = 1, int $cursoId = 1): Turma
    {
        $t = new Turma('T', 'desc', 30, StatusTurma::Disponivel,
            new DateTime('2026-06-01'), new DateTime('2026-12-01'), $cursoId);
        $t->setId($id);
        return $t;
    }

    private function makeCurso(int $id = 1): Curso
    {
        $c = new Curso('PHP', 'desc', Temas::Tecnologia, 'img.jpg');
        $c->setId($id);
        return $c;
    }

    public function test_store_validates_curso_exists(): void
    {
        $this->cursoRepo->method('find')->with(1)->willReturn($this->makeCurso());
        $data  = ['titulo' => 'T', 'descricao' => 'd', 'quantidade_vagas' => 10,
                  'status' => 'disponivel', 'data_inicio' => '2026-06-01', 'data_fim' => '2026-12-01'];
        $turma = $this->makeTurma();
        $this->turmaRepo->expects($this->once())
            ->method('store')
            ->with($this->isInstanceOf(Turma::class))
            ->willReturn($turma);

        $result = $this->service->store(1, $data);
        $this->assertSame($turma, $result);
    }

    public function test_store_throws_when_curso_not_found(): void
    {
        $this->cursoRepo->method('find')->willThrowException(new NotFound());
        $this->expectException(NotFound::class);
        $this->service->store(99, []);
    }

    public function test_find_delegates_to_repo(): void
    {
        $turma = $this->makeTurma();
        $this->turmaRepo->method('find')->with(1)->willReturn($turma);
        $this->assertSame($turma, $this->service->find(1));
    }

    public function test_update_delegates_to_repo(): void
    {
        $existing = $this->makeTurma();
        $this->turmaRepo->method('find')->with(1)->willReturn($existing);

        $data    = ['titulo' => 'T2', 'descricao' => 'd', 'quantidade_vagas' => 5,
                    'status' => 'encerrado', 'data_inicio' => '2026-06-01', 'data_fim' => '2026-12-01'];
        $updated = $this->makeTurma();
        $this->turmaRepo->expects($this->once())->method('update')
            ->with(1, $this->isInstanceOf(Turma::class))
            ->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, $data));
    }

    public function test_destroy_delegates_to_repo(): void
    {
        $this->turmaRepo->expects($this->once())->method('destroy')->with(1);
        $this->service->destroy(1);
    }
}
