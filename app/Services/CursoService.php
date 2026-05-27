<?php

namespace App\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Services\CursoServiceInterface;
use App\Entities\Curso;

class CursoService implements CursoServiceInterface
{
    public function __construct(private CursoRepositoryInterface $repo)
    {
    }

    public function get(array $filters = []): array
    {
        return $this->repo->get($filters);
    }

    public function getAvailable(array $filters = []): array
    {
        return $this->repo->getWithAvailableTurmas($filters);
    }

    public function find(int $id): Curso
    {
        return $this->repo->find($id);
    }

    public function store(mixed $data): Curso
    {
        return $this->repo->store($data);
    }

    public function update(int $id, mixed $data): Curso
    {
        return $this->repo->update($id, $data);
    }

    public function destroy(int $id): void
    {
        $this->repo->destroy($id);
    }
}
