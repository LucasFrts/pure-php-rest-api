<?php

namespace App\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Services\CursoServiceInterface;
use App\Entities\Curso;
use App\Enums\Temas;
use Psr\Log\LoggerInterface;

class CursoService implements CursoServiceInterface
{
    public function __construct(
        private CursoRepositoryInterface $repo,
        private LoggerInterface $logger
    ) {
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

        $result = $this->repo->store($curso);
        $this->logger->info("CursoService::store cursoId={$result->getId()}");
        return $result;
    }

    public function update(int $id, mixed $data): Curso
    {
        $existing = $this->repo->find($id);

        $curso = new Curso(
            $data['titulo']     ?? $existing->getTitulo(),
            $data['descricao']  ?? $existing->getDescricao(),
            isset($data['tema']) ? Temas::fromString($data['tema']) : $existing->getTema(),
            $data['url_imagem'] ?? $existing->getUrlImagem()
        );

        $result = $this->repo->update($id, $curso);
        $this->logger->info("CursoService::update cursoId={$id}");
        return $result;
    }

    public function destroy(int $id): void
    {
        $this->repo->destroy($id);
        $this->logger->info("CursoService::destroy cursoId={$id}");
    }
}
