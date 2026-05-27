<?php

namespace Tests\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Entities\Curso;
use App\Entities\Turma;
use App\Enums\StatusTurma;
use App\Enums\Temas;
use App\Exceptions\Http\NotFound;
use App\Services\TurmaService;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TurmaServiceTest extends TestCase
{
    private TurmaRepositoryInterface $turmaRepo;
    private CursoRepositoryInterface $cursoRepo;
    private MatriculaRepositoryInterface $matriculaRepo;
    private LoggerInterface $logger;
    private TurmaService $service;

    protected function setUp(): void
    {
        $this->turmaRepo     = $this->createMock(TurmaRepositoryInterface::class);
        $this->cursoRepo     = $this->createMock(CursoRepositoryInterface::class);
        $this->matriculaRepo = $this->createMock(MatriculaRepositoryInterface::class);
        $this->logger        = $this->createMock(LoggerInterface::class);
        $this->service       = new TurmaService(
            $this->turmaRepo,
            $this->cursoRepo,
            $this->matriculaRepo,
            $this->logger
        );
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

    public function test_update_partial_falls_back_to_existing_fields(): void
    {
        $existing = $this->makeTurma();
        $updated  = $this->makeTurma();
        $this->turmaRepo->method('find')->with(1)->willReturn($existing);
        $this->turmaRepo->expects($this->once())->method('update')
            ->with(1, $this->callback(function (Turma $t) use ($existing) {
                return $t->getTitulo()          === 'Novo Titulo'
                    && $t->getDescricao()       === $existing->getDescricao()
                    && $t->getQuantidadeVagas() === $existing->getQuantidadeVagas()
                    && $t->getStatus()          === $existing->getStatus()
                    && $t->getCursoId()         === $existing->getCursoId();
            }))
            ->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, ['titulo' => 'Novo Titulo']));
    }

    public function test_destroy_delegates_to_repo(): void
    {
        $this->turmaRepo->expects($this->once())->method('destroy')->with(1);
        $this->service->destroy(1);
    }

    public function test_update_calls_inativar_by_turma_when_transitioning_to_encerrado(): void
    {
        $existing = $this->makeTurma(id: 1);
        $this->turmaRepo->method('find')->with(1)->willReturn($existing);

        $encerrada = new Turma('T', 'desc', 30, StatusTurma::Encerrado,
            new DateTime('2026-06-01'), new DateTime('2026-12-01'), 1);
        $encerrada->setId(1);
        $this->turmaRepo->method('update')->willReturn($encerrada);

        $this->matriculaRepo->expects($this->once())
            ->method('inativarByTurma')
            ->with(1);

        $this->service->update(1, ['status' => 'encerrado']);
    }

    public function test_update_does_not_call_inativar_when_already_encerrada(): void
    {
        $existing = new Turma('T', 'desc', 30, StatusTurma::Encerrado,
            new DateTime('2026-06-01'), new DateTime('2026-12-01'), 1);
        $existing->setId(1);
        $this->turmaRepo->method('find')->with(1)->willReturn($existing);

        $encerrada = clone $existing;
        $this->turmaRepo->method('update')->willReturn($encerrada);

        $this->matriculaRepo->expects($this->never())->method('inativarByTurma');

        $this->service->update(1, ['status' => 'encerrado']);
    }

    public function test_store_logs_info_on_success(): void
    {
        $turma = $this->makeTurma(5);
        $this->cursoRepo->method('find')->willReturn($this->makeCurso());
        $this->turmaRepo->method('store')->willReturn($turma);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('TurmaService::store turmaId=5'));
        $this->service->store(1, [
            'titulo' => 'T', 'descricao' => 'd', 'quantidade_vagas' => 10,
            'status' => 'disponivel', 'data_inicio' => '2026-06-01', 'data_fim' => '2026-12-01',
        ]);
    }

    public function test_update_logs_info_on_success(): void
    {
        $existing = $this->makeTurma(3);
        $updated  = $this->makeTurma(3);
        $this->turmaRepo->method('find')->willReturn($existing);
        $this->turmaRepo->method('update')->willReturn($updated);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('TurmaService::update turmaId=3'));
        $this->service->update(3, ['titulo' => 'Novo']);
    }

    public function test_destroy_logs_info_on_success(): void
    {
        $this->turmaRepo->method('destroy')->with(9);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('TurmaService::destroy turmaId=9'));
        $this->service->destroy(9);
    }
}
