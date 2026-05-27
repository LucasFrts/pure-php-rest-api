<?php

namespace App\Http\Controllers;

use App\Contracts\ResponseInterface;
use App\Contracts\Services\UsuarioServiceInterface;

class UsuariosController extends BaseController
{
    public function __construct(private UsuarioServiceInterface $service)
    {
    }

    public function store(): ResponseInterface
    {
        $usuario = $this->service->store($this->request()->data());
        return $this->response()->created(['data' => $usuario]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }
}
