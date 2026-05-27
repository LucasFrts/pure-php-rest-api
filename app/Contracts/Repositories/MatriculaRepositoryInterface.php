<?php

namespace App\Contracts\Repositories;

use App\Entities\Matricula;

interface MatriculaRepositoryInterface
{
    public function store(Matricula $matricula): Matricula;

    public function findByUsuarioAndCurso(int $usuarioId, int $cursoId): ?Matricula;

    public function getByUsuario(int $usuarioId): array;

    public function destroy(int $id): void;
}
