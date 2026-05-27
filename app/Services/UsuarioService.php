<?php

namespace App\Services;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Contracts\Services\UsuarioServiceInterface;
use App\Entities\Usuario;
use App\Exceptions\Http\UnprocessableEntity;
use App\ValueObjects\Email;
use Psr\Log\LoggerInterface;

class UsuarioService implements UsuarioServiceInterface
{
    public function __construct(
        private UsuarioRepositoryInterface $repo,
        private LoggerInterface $logger
    ) {
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
        try {
            $usuario = new Usuario($data['nome'], new Email($data['email']));
        } catch (\InvalidArgumentException $e) {
            $this->logger->error("UsuarioService::store FAILED: {$e->getMessage()}");
            throw new UnprocessableEntity($e->getMessage());
        }

        $result = $this->repo->store($usuario);
        $this->logger->info("UsuarioService::store usuarioId={$result->getId()}");
        return $result;
    }

    public function update(int $id, mixed $data): Usuario
    {
        $existing = $this->repo->find($id);

        try {
            $email = isset($data['email']) ? new Email($data['email']) : $existing->getEmail();
        } catch (\InvalidArgumentException $e) {
            $this->logger->error("UsuarioService::update FAILED: {$e->getMessage()}");
            throw new UnprocessableEntity($e->getMessage());
        }

        $usuario = new Usuario(
            $data['nome'] ?? $existing->getNome(),
            $email
        );

        $result = $this->repo->update($id, $usuario);
        $this->logger->info("UsuarioService::update usuarioId={$id}");
        return $result;
    }

    public function destroy(int $id): void
    {
        $this->repo->destroy($id);
        $this->logger->info("UsuarioService::destroy usuarioId={$id}");
    }
}
