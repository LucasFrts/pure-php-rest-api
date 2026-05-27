<?php

namespace App\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Contracts\Services\TurmaServiceInterface;
use App\Entities\StatusTurma;
use App\Entities\Turma;

class TurmaService implements TurmaServiceInterface
{
    public function __construct(
        private TurmaRepositoryInterface $turmaRepo,
        private CursoRepositoryInterface $cursoRepo
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
            new \DateTime($data['data_inicio']),
            new \DateTime($data['data_fim']),
            $cursoId
        );

        return $this->turmaRepo->store($turma);
    }

    public function update(int $id, mixed $data): Turma
    {
        $existing = $this->turmaRepo->find($id);

        $turma = new Turma(
            $data['titulo'],
            $data['descricao'],
            (int) $data['quantidade_vagas'],
            StatusTurma::fromString($data['status']),
            new \DateTime($data['data_inicio']),
            new \DateTime($data['data_fim']),
            $existing->getCursoId()
        );

        return $this->turmaRepo->update($id, $turma);
    }

    public function destroy(int $id): void
    {
        $this->turmaRepo->destroy($id);
    }
}
