<?php

namespace App\Contracts\Services;

use App\Entities\Usuario;

/**
 * 
 * @see App\Services\UsuarioService
 * 
 */
interface UsuarioServiceInterface
{
    public function get(array $filters = []) : array;
    public function find(int $id) : Usuario;
    public function store(mixed $data) : Usuario;
    public function update(int $id, mixed $data) : Usuario;
    public function destroy(int $id) : void;
}