<?php

namespace App\Contracts\Repositories;

use App\Entities\Matricula;
use App\Entities\StatusMatricula;

interface MatriculaRepositoryInterface
{
    public function store(Matricula $matricula): Matricula;

    public function find(int $id): Matricula;

    public function findByUsuarioAndCurso(int $usuarioId, int $cursoId): ?Matricula;

    public function getByUsuario(int $usuarioId): array;

    public function updateStatus(int $id, StatusMatricula $status): Matricula;

    public function inativarByTurma(int $turmaId): void;

    public function destroy(int $id): void;
}
