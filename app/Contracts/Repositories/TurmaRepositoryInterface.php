<?php

namespace App\Contracts\Repositories;

use App\Entities\Turma;

interface TurmaRepositoryInterface
{
    public function get(array $filters = []): array;

    public function find(int $id): Turma;

    public function store(mixed $data): Turma;

    public function update(int $id, mixed $data): Turma;

    public function destroy(int $id): void;

    public function findByCursoId(int $cursoId): array;
}