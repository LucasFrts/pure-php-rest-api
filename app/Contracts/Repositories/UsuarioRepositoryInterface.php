<?php

namespace App\Contracts\Repositories;

use App\Entities\Usuario;

interface UsuarioRepositoryInterface
{
    public function get(array $filters = []) : array;
    public function find(int $id) : Usuario;
    public function store(Usuario $usuario) : Usuario;
    public function update(int $id, Usuario $usuario) : Usuario;
    public function destroy(int $id) : void;
}