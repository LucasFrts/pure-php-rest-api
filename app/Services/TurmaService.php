<?php

namespace App\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Contracts\Services\TurmaServiceInterface;
use App\Entities\StatusTurma;
use App\Entities\Turma;
use DateTime;

class TurmaService implements TurmaServiceInterface
{
    public function __construct(
        private TurmaRepositoryInterface $turmaRepo,
        private CursoRepositoryInterface $cursoRepo,
        private MatriculaRepositoryInterface $matriculaRepo
    ) {
    }

    public function get(array $filters = []): array
    {
        return $this->turmaRepo->get($filters);
    }

    public function find(int $id): Turma
    {
        return $this->turmaRepo->find($id);
    }

    public function store(int $cursoId, mixed $data): Turma
    {
        $this->cursoRepo->find($cursoId);

        $turma = new Turma(
            $data['titulo'],
            $data['descricao'],
            (int) $data['quantidade_vagas'],
            StatusTurma::fromString($data['status']),
            new DateTime($data['data_inicio']),
            new DateTime($data['data_fim']),
            $cursoId
        );

        return $this->turmaRepo->store($turma);
    }

    public function update(int $id, mixed $data): Turma
    {
        $existing = $this->turmaRepo->find($id);

        $newStatus = isset($data['status'])
            ? StatusTurma::fromString($data['status'])
            : $existing->getStatus();

        $turma = new Turma(
            $data['titulo']          ?? $existing->getTitulo(),
            $data['descricao']       ?? $existing->getDescricao(),
            isset($data['quantidade_vagas']) ? (int) $data['quantidade_vagas'] : $existing->getQuantidadeVagas(),
            $newStatus,
            isset($data['data_inicio']) ? new DateTime($data['data_inicio']) : $existing->getDataInicio(),
            isset($data['data_fim'])    ? new DateTime($data['data_fim'])    : $existing->getDataFim(),
            $existing->getCursoId()
        );

        $updated = $this->turmaRepo->update($id, $turma);

        if ($existing->getStatus() !== StatusTurma::Encerrado && $newStatus === StatusTurma::Encerrado) {
            $this->matriculaRepo->inativarByTurma($id);
        }

        return $updated;
    }

    public function destroy(int $id): void
    {
        $this->turmaRepo->destroy($id);
    }
}
