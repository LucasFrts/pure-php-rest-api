<?php

namespace App\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Services\CursoServiceInterface;
use App\Entities\Curso;
use App\Entities\Temas;

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
        $curso = new Curso(
            $data['titulo'],
            $data['descricao'],
            Temas::fromString($data['tema']),
            $data['url_imagem']
        );

        return $this->repo->store($curso);
    }

    public function update(int $id, mixed $data): Curso
    {
        $curso = new Curso(
            $data['titulo'],
            $data['descricao'],
            Temas::fromString($data['tema']),
            $data['url_imagem']
        );

        return $this->repo->update($id, $curso);
    }

    public function destroy(int $id): void
    {
        $this->repo->destroy($id);
    }
}
