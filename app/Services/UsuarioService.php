<?php

namespace App\Services;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Contracts\Services\UsuarioServiceInterface;
use App\Entities\Usuario;

class UsuarioService implements UsuarioServiceInterface
{
    public function __construct(private UsuarioRepositoryInterface $repo)
    {
    }

    public function get(array $filters = []): array
    {
        return $this->repo->get($filters);
    }

    public function find(int $id): Usuario
    {
        return $this->repo->find($id);
    }

    public function store(mixed $data): Usuario
    {
        return $this->repo->store($data);
    }

    public function update(int $id, mixed $data): Usuario
    {
        return $this->repo->update($id, $data);
    }

    public function destroy(int $id): void
    {
        $this->repo->destroy($id);
    }
}
