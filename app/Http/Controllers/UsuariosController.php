<?php

namespace App\Http\Controllers;

use App\Contracts\ResponseInterface;
use App\Contracts\Services\UsuarioServiceInterface;

class UsuariosController extends BaseController
{
    public function __construct(private UsuarioServiceInterface $service)
    {
    }

    public function index(): ResponseInterface
    {
        return $this->response()->success(['data' => $this->service->get()]);
    }

    public function store(): ResponseInterface
    {
        $data = $this->request()->data();
        $this->validator()->requireFields($data, ['nome', 'email']);

        $usuario = $this->service->store($data);
        return $this->response()->created(['data' => $usuario]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }
}
