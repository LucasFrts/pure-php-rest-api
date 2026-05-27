<?php

namespace App\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\MatriculaServiceInterface;
use App\Entities\Matricula;
use App\Support\Container;

class MatriculasController extends BaseController
{
    public function __construct(private MatriculaServiceInterface $service)
    {
    }

    public function store(): ResponseInterface
    {
        $request   = Container::getContainer()->get(RequestInterface::class);
        $data      = $request->getJSON();
        $matricula = $this->service->enroll((int) $data['usuario_id'], (int) $data['turma_id']);
        return $this->response()->created(['data' => $this->serialize($matricula)]);
    }

    public function index(int $usuarioId): ResponseInterface
    {
        $matriculas = $this->service->getByUsuario($usuarioId);
        return $this->response()->success(['data' => $matriculas]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }

    private function serialize(Matricula $matricula): array
    {
        return [
            'id'         => $matricula->getId(),
            'usuario_id' => $matricula->getUsuarioId(),
            'turma_id'   => $matricula->getTurmaId(),
            'curso_id'   => $matricula->getCursoId(),
            'status'     => $matricula->getStatus()->toString(),
        ];
    }
}
