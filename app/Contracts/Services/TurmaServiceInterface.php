<?php

namespace App\Contracts\Services;

use App\Entities\Turma;

interface TurmaServiceInterface
{
    public function get(array $filters = []) : array;
    public function find(int $id) : Turma;
    public function store(int $cursoId, mixed $data) : Turma;
    public function update(int $id, mixed $data) : Turma;
    public function destroy(int $id) : void;
}