<?php

namespace App\Contracts\Repositories;

use App\Entities\Curso;

interface CursoRepositoryInterface
{
    public function get(array $filters = []): array;

    public function find(int $id): Curso;

    public function store(mixed $data): Curso;

    public function update(int $id, mixed $data): Curso;

    public function destroy(int $id): void;

    public function getWithAvailableTurmas(array $filters = []): array;
}