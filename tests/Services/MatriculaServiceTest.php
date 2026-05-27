<?php

namespace Tests\Services;

use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Entities\Matricula;
use App\Entities\StatusTurma;
use App\Entities\Turma;
use App\Exceptions\Http\NotFound;
use App\Exceptions\Http\UnprocessableEntity;
use App\Services\MatriculaService;
use DateTime;
use PHPUnit\Framework\TestCase;

class MatriculaServiceTest extends TestCase
{
    private MatriculaRepositoryInterface $matriculaRepo;
    private TurmaRepositoryInterface $turmaRepo;
    private MatriculaService $service;

    protected function setUp(): void
    {
        $this->matriculaRepo = $this->createMock(MatriculaRepositoryInterface::class);
        $this->turmaRepo     = $this->createMock(TurmaRepositoryInterface::class);
        $this->service       = new MatriculaService($this->matriculaRepo, $this->turmaRepo);
    }

    private function makeAvailableTurma(int $id = 1, int $cursoId = 1): Turma
    {
        $t = new Turma('T', 'desc', 30, StatusTurma::Disponivel,
            new DateTime('-1 day'), new DateTime('+1 day'), $cursoId);
        $t->setId($id);
        return $t;
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
}
