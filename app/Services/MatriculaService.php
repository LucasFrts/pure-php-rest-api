<?php

namespace App\Services;

use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Contracts\Services\MatriculaServiceInterface;
use App\Entities\Matricula;
use App\Entities\StatusMatricula;
use App\Entities\StatusTurma;
use App\Exceptions\Http\UnprocessableEntity;
use DateTime;

class MatriculaService implements MatriculaServiceInterface
{
    public function __construct(
        private MatriculaRepositoryInterface $matriculaRepo,
        private TurmaRepositoryInterface $turmaRepo
    ) {
    }

    public function enroll(int $usuarioId, int $turmaId): Matricula
    {
        $turma = $this->turmaRepo->find($turmaId);

        if ($turma->getStatus() !== StatusTurma::Disponivel) {
            throw new UnprocessableEntity('Turma está encerrada');
        }

        $now = new DateTime();

        if ($now < $turma->getDataInicio() || $now > $turma->getDataFim()) {
            throw new UnprocessableEntity('Fora do período de matrícula');
        }

        if ($this->matriculaRepo->findByUsuarioAndCurso($usuarioId, $turma->getCursoId()) !== null) {
            throw new UnprocessableEntity('Usuário já matriculado em uma turma deste curso');
        }

        return $this->matriculaRepo->store(new Matricula($usuarioId, $turmaId, $turma->getCursoId()));
    }

    public function getByUsuario(int $usuarioId): array
    {
        return $this->matriculaRepo->getByUsuario($usuarioId);
    }

    public function updateStatus(int $id, string $status): Matricula
    {
        $matricula = $this->matriculaRepo->find($id);
        $turma     = $this->turmaRepo->find($matricula->getTurmaId());

        if ($turma->getStatus() === StatusTurma::Encerrado) {
            throw new UnprocessableEntity('Turma encerrada');
        }

        try {
            $statusEnum = StatusMatricula::fromString($status);
        } catch (\ValueError) {
            throw new UnprocessableEntity("Status inválido: {$status}");
        }

        return $this->matriculaRepo->updateStatus($id, $statusEnum);
    }

    public function destroy(int $id): void
    {
        $this->matriculaRepo->destroy($id);
    }
}
