<?php

namespace App\Services;

use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Contracts\Services\MatriculaServiceInterface;
use App\Entities\Matricula;
use App\Enums\StatusMatricula;
use App\Enums\StatusTurma;
use App\Exceptions\Http\UnprocessableEntity;
use DateTime;
use Psr\Log\LoggerInterface;

class MatriculaService implements MatriculaServiceInterface
{
    public function __construct(
        private MatriculaRepositoryInterface $matriculaRepo,
        private TurmaRepositoryInterface $turmaRepo,
        private LoggerInterface $logger
    ) {
    }

    public function enroll(int $usuarioId, int $turmaId): Matricula
    {
        $turma = $this->turmaRepo->find($turmaId);

        try {
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

            if ($turma->getQuantidadeVagas() <= 0) {
                throw new UnprocessableEntity("Esta turma já atingiu o limite máximo de vagas.");
            }

        } catch (UnprocessableEntity $e) {
            $this->logger->error("MatriculaService::enroll FAILED: {$e->getMessage()}");
            throw $e;
        }

        // adicionar transição depois
        $turma->decrementVaga();
        $result = $this->matriculaRepo->store(new Matricula($usuarioId, $turmaId, $turma->getCursoId()));
        $this->turmaRepo->update($turmaId, $turma);

        $this->logger->info("MatriculaService::enroll usuarioId={$usuarioId} turmaId={$turmaId} → matriculaId={$result->getId()}");
        return $result;
    }

    public function getByUsuario(int $usuarioId): array
    {
        return $this->matriculaRepo->getByUsuario($usuarioId);
    }

    public function updateStatus(int $id, string $status): Matricula
    {
        $matricula = $this->matriculaRepo->find($id);
        $turma     = $this->turmaRepo->find($matricula->getTurmaId());

        try {
            if ($turma->getStatus() === StatusTurma::Encerrado) {
                throw new UnprocessableEntity('Turma encerrada');
            }

            try {
                $statusEnum = StatusMatricula::fromString($status);
            } catch (\ValueError) {
                throw new UnprocessableEntity("Status inválido: {$status}");
            }
        } catch (UnprocessableEntity $e) {
            $this->logger->error("MatriculaService::updateStatus FAILED: {$e->getMessage()}");
            throw $e;
        }

        $result = $this->matriculaRepo->updateStatus($id, $statusEnum);
        $this->logger->info("MatriculaService::updateStatus matriculaId={$id} → {$statusEnum->name}");
        return $result;
    }

    public function destroy(int $id): void
    {
        $this->matriculaRepo->destroy($id);
        $this->logger->info("MatriculaService::destroy matriculaId={$id}");
    }
}
