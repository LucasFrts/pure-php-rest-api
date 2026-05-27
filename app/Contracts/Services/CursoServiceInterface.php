<?php

namespace App\Contracts\Services;

use App\Entities\Curso;

interface CursoServiceInterface
{
    public function get(array $filters = []): array;
    public function getAvailable(array $filters = []): array;
    public function find(int $id): Curso;
    public function store(mixed $data) : Curso;
    public function update(int $id, mixed $data) : Curso;
    public function destroy(int $id) : void;
}