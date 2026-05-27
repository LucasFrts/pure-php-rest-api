<?php

namespace App\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\UsuarioServiceInterface;
use App\Support\Container;

class UsuariosController extends BaseController
{
    public function __construct(private UsuarioServiceInterface $service)
    {
    }

    public function store(): ResponseInterface
    {
        $request = Container::getContainer()->get(RequestInterface::class);
        $data    = $request->getJSON();
        $usuario = $this->service->store($data);
        return $this->response()->created([
            'data' => [
                'id'    => $usuario->getId(),
                'nome'  => $usuario->getNome(),
                'email' => $usuario->getEmail()->getValue(),
            ],
        ]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }
}
