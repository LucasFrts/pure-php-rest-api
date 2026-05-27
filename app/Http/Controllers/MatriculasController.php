<?php

namespace App\Http\Controllers;

use App\Contracts\ResponseInterface;
use App\Contracts\Services\MatriculaServiceInterface;

class MatriculasController extends BaseController
{
    public function __construct(private MatriculaServiceInterface $service)
    {
    }

    public function store(): ResponseInterface
    {
        $data      = $this->request()->data();
        $matricula = $this->service->enroll((int) $data['usuario_id'], (int) $data['turma_id']);
        return $this->response()->created(['data' => $matricula]);
    }

    public function index(int $usuarioId): ResponseInterface
    {
        return $this->response()->success(['data' => $this->service->getByUsuario($usuarioId)]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }
}
