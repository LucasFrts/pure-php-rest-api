<?php

namespace App\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Contracts\Services\TurmaServiceInterface;
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
        $data['curso_id'] = $cursoId;

        return $this->turmaRepo->store($data);
    }

    public function update(int $id, mixed $data): Turma
    {
        return $this->turmaRepo->update($id, $data);
    }

    public function destroy(int $id): void
    {
        $this->turmaRepo->destroy($id);
    }
}
