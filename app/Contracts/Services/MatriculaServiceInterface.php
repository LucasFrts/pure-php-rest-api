<?php

namespace App\Contracts\Services;

use App\Entities\Matricula;

/**
 * 
 * @see App\Services\MatriculaService
 * 
 */
interface MatriculaServiceInterface
{
    public function enroll(int $usuarioId, int $turmaId): Matricula;

    public function getByUsuario(int $usuarioId): array;

    public function updateStatus(int $id, string $status): Matricula;

    public function destroy(int $id): void;
}
