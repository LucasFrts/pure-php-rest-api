<?php

namespace App\Contracts\Repositories;

use App\Entities\Turma;

/**
 * 
 * @see App\Repositories\TurmaRepository
 * 
 */
interface TurmaRepositoryInterface
{
    public function get(array $filters = []): array;

    public function find(int $id): Turma;

    public function store(Turma $turma): Turma;

    public function update(int $id, Turma $turma): Turma;

    public function destroy(int $id): void;

    public function findByCursoId(int $cursoId): array;
}