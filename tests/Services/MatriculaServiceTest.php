<?php

namespace Tests\Services;

use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Entities\Matricula;
use App\Enums\StatusMatricula;
use App\Enums\StatusTurma;
use App\Entities\Turma;
use App\Exceptions\Http\NotFound;
use App\Exceptions\Http\UnprocessableEntity;
use App\Services\MatriculaService;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MatriculaServiceTest extends TestCase
{
    private MatriculaRepositoryInterface $matriculaRepo;
    private TurmaRepositoryInterface $turmaRepo;
    private LoggerInterface $logger;
    private MatriculaService $service;

    protected function setUp(): void
    {
        $this->matriculaRepo = $this->createMock(MatriculaRepositoryInterface::class);
        $this->turmaRepo     = $this->createMock(TurmaRepositoryInterface::class);
        $this->logger        = $this->createMock(LoggerInterface::class);
        $this->service       = new MatriculaService($this->matriculaRepo, $this->turmaRepo, $this->logger);
    }

    private function makeAvailableTurma(int $id = 1, int $cursoId = 1): Turma
    {
        $t = new Turma('T', 'desc', 30, StatusTurma::Disponivel,
            new DateTime('-1 day'), new DateTime('+1 day'), $cursoId);
        $t->setId($id);
        return $t;
    }

    private function makeMatricula(int $id = 1, int $turmaId = 1): Matricula
    {
        $m = new Matricula(1, $turmaId, 1);
        $m->setId($id);
        return $m;
    }

    public function test_enroll_succeeds_for_available_turma(): void
    {
        $turma = $this->makeAvailableTurma();
        $this->turmaRepo->method('find')->with(1)->willReturn($turma);
        $this->matriculaRepo->method('findByUsuarioAndCurso')->willReturn(null);

        $stored = new Matricula(1, 1, 1);
        $stored->setId(10);
        $this->matriculaRepo->expects($this->once())->method('store')->willReturn($stored);

        $result = $this->service->enroll(1, 1);
        $this->assertSame(10, $result->getId());
    }

    public function test_enroll_throws_when_turma_not_found(): void
    {
        $this->turmaRepo->method('find')->willThrowException(new NotFound());
        $this->expectException(NotFound::class);
        $this->service->enroll(1, 99);
    }

    public function test_enroll_throws_when_turma_encerrada(): void
    {
        $t = new Turma('T', 'desc', 10, StatusTurma::Encerrado,
            new DateTime('-1 day'), new DateTime('+1 day'), 1);
        $t->setId(1);
        $this->turmaRepo->method('find')->willReturn($t);

        $this->expectException(UnprocessableEntity::class);
        $this->service->enroll(1, 1);
    }

    public function test_enroll_throws_when_before_data_inicio(): void
    {
        $t = new Turma('T', 'desc', 10, StatusTurma::Disponivel,
            new DateTime('+5 days'), new DateTime('+30 days'), 1);
        $t->setId(1);
        $this->turmaRepo->method('find')->willReturn($t);

        $this->expectException(UnprocessableEntity::class);
        $this->service->enroll(1, 1);
    }

    public function test_enroll_throws_when_after_data_fim(): void
    {
        $t = new Turma('T', 'desc', 10, StatusTurma::Disponivel,
            new DateTime('-30 days'), new DateTime('-5 days'), 1);
        $t->setId(1);
        $this->turmaRepo->method('find')->willReturn($t);

        $this->expectException(UnprocessableEntity::class);
        $this->service->enroll(1, 1);
    }

    public function test_enroll_throws_when_already_enrolled_in_same_course(): void
    {
        $turma    = $this->makeAvailableTurma();
        $existing = new Matricula(1, 1, 1);
        $this->turmaRepo->method('find')->willReturn($turma);
        $this->matriculaRepo->method('findByUsuarioAndCurso')
            ->with(1, 1)->willReturn($existing);

        $this->expectException(UnprocessableEntity::class);
        $this->service->enroll(1, 1);
    }

    public function test_get_by_usuario_delegates_to_repo(): void
    {
        $expected = [new Matricula(1, 2, 3)];
        $this->matriculaRepo->method('getByUsuario')->with(1)->willReturn($expected);
        $this->assertSame($expected, $this->service->getByUsuario(1));
    }

    public function test_destroy_delegates_to_repo(): void
    {
        $this->matriculaRepo->expects($this->once())->method('destroy')->with(5);
        $this->service->destroy(5);
    }

    public function test_update_status_returns_updated_matricula(): void
    {
        $matricula = $this->makeMatricula(turmaId: 1);
        $turma     = $this->makeAvailableTurma(id: 1);
        $updated   = $this->makeMatricula(turmaId: 1);
        $updated->setStatus(StatusMatricula::Inativo);

        $this->matriculaRepo->method('find')->with(1)->willReturn($matricula);
        $this->turmaRepo->method('find')->with(1)->willReturn($turma);
        $this->matriculaRepo->method('updateStatus')
            ->with(1, StatusMatricula::Inativo)
            ->willReturn($updated);

        $result = $this->service->updateStatus(1, 'inativo');
        $this->assertSame(StatusMatricula::Inativo, $result->getStatus());
    }

    public function test_update_status_throws_when_matricula_not_found(): void
    {
        $this->matriculaRepo->method('find')->willThrowException(new NotFound());
        $this->expectException(NotFound::class);
        $this->service->updateStatus(99, 'ativo');
    }

    public function test_update_status_throws_when_turma_encerrada(): void
    {
        $matricula = $this->makeMatricula(turmaId: 1);
        $turma     = new Turma('T', 'desc', 10, StatusTurma::Encerrado,
            new DateTime('-30 days'), new DateTime('-1 day'), 1);
        $turma->setId(1);

        $this->matriculaRepo->method('find')->willReturn($matricula);
        $this->turmaRepo->method('find')->willReturn($turma);

        $this->expectException(UnprocessableEntity::class);
        $this->service->updateStatus(1, 'cancelado');
    }

    public function test_update_status_throws_for_invalid_status_string(): void
    {
        $matricula = $this->makeMatricula(turmaId: 1);
        $turma     = $this->makeAvailableTurma(id: 1);

        $this->matriculaRepo->method('find')->willReturn($matricula);
        $this->turmaRepo->method('find')->willReturn($turma);

        $this->expectException(UnprocessableEntity::class);
        $this->service->updateStatus(1, 'invalido');
    }

    public function test_enroll_logs_info_on_success(): void
    {
        $turma = $this->makeAvailableTurma(id: 3);
        $this->turmaRepo->method('find')->willReturn($turma);
        $this->matriculaRepo->method('findByUsuarioAndCurso')->willReturn(null);

        $stored = new Matricula(5, 3, 1);
        $stored->setId(42);
        $this->matriculaRepo->method('store')->willReturn($stored);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('MatriculaService::enroll usuarioId=5 turmaId=3 → matriculaId=42'));

        $this->service->enroll(5, 3);
    }

    public function test_enroll_logs_error_on_turma_encerrada(): void
    {
        $t = new Turma('T', 'desc', 10, StatusTurma::Encerrado,
            new DateTime('-1 day'), new DateTime('+1 day'), 1);
        $t->setId(1);
        $this->turmaRepo->method('find')->willReturn($t);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('MatriculaService::enroll FAILED: Turma está encerrada'));

        $this->expectException(UnprocessableEntity::class);
        $this->service->enroll(1, 1);
    }

    public function test_enroll_logs_error_on_periodo_invalido(): void
    {
        $t = new Turma('T', 'desc', 10, StatusTurma::Disponivel,
            new DateTime('+5 days'), new DateTime('+30 days'), 1);
        $t->setId(1);
        $this->turmaRepo->method('find')->willReturn($t);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('MatriculaService::enroll FAILED:'));

        $this->expectException(UnprocessableEntity::class);
        $this->service->enroll(1, 1);
    }

    public function test_updateStatus_logs_info_on_success(): void
    {
        $matricula = $this->makeMatricula(id: 7, turmaId: 1);
        $turma     = $this->makeAvailableTurma(id: 1);
        $updated   = $this->makeMatricula(id: 7, turmaId: 1);
        $updated->setStatus(StatusMatricula::Inativo);

        $this->matriculaRepo->method('find')->willReturn($matricula);
        $this->turmaRepo->method('find')->willReturn($turma);
        $this->matriculaRepo->method('updateStatus')->willReturn($updated);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('MatriculaService::updateStatus matriculaId=7 → Inativo'));

        $this->service->updateStatus(7, 'inativo');
    }

    public function test_updateStatus_logs_error_on_invalid_status(): void
    {
        $matricula = $this->makeMatricula(turmaId: 1);
        $turma     = $this->makeAvailableTurma(id: 1);

        $this->matriculaRepo->method('find')->willReturn($matricula);
        $this->turmaRepo->method('find')->willReturn($turma);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('MatriculaService::updateStatus FAILED:'));

        $this->expectException(UnprocessableEntity::class);
        $this->service->updateStatus(1, 'invalido');
    }

    public function test_destroy_logs_info_on_success(): void
    {
        $this->matriculaRepo->method('destroy')->with(5);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('MatriculaService::destroy matriculaId=5'));
        $this->service->destroy(5);
    }
}
