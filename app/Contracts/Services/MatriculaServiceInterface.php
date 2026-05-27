<?php

namespace App\Contracts\Services;

use App\Entities\Matricula;

interface MatriculaServiceInterface
{
    public function enroll(int $usuarioId, int $turmaId): Matricula;

    public function getByUsuario(int $usuarioId): array;

    public function destroy(int $id): void;
}
