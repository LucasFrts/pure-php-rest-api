<?php

namespace App\Contracts\Repositories;

use App\Entities\Curso;

interface CursoRepositoryInterface
{
    public function get(array $filters = []): array;

    public function find(int $id): Curso;

    public function store(Curso $curso): Curso;

    public function update(int $id, Curso $curso): Curso;

    public function destroy(int $id): void;

    public function getWithAvailableTurmas(array $filters = []): array;
}