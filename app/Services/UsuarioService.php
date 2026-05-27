<?php

namespace App\Services;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Contracts\Services\UsuarioServiceInterface;
use App\Entities\Usuario;
use App\ValueObjects\Email;

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
        $usuario = new Usuario($data['nome'], new Email($data['email']));

        return $this->repo->store($usuario);
    }

    public function update(int $id, mixed $data): Usuario
    {
        $existing = $this->repo->find($id);

        $usuario = new Usuario(
            $data['nome']  ?? $existing->getNome(),
            isset($data['email']) ? new Email($data['email']) : $existing->getEmail()
        );

        return $this->repo->update($id, $usuario);
    }

    public function destroy(int $id): void
    {
        $this->repo->destroy($id);
    }
}
