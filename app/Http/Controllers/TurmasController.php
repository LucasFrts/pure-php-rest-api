<?php

namespace App\Http\Controllers;

use App\Contracts\ResponseInterface;
use App\Contracts\Services\TurmaServiceInterface;
use App\Enums\StatusTurma;

class TurmasController extends BaseController
{
    public function __construct(private TurmaServiceInterface $service)
    {
    }

    public function store(int $cursoId): ResponseInterface
    {
        $data = $this->request()->data();
        $this->validator()->requireFields($data, [
            'titulo',
            'descricao',
            'quantidade_vagas',
            'status',
            'data_inicio',
            'data_fim',
        ]);
        $this->validator()->parseEnum('status', $data['status'], StatusTurma::fromString(...));
        $this->validator()->parseDate('data_inicio', $data['data_inicio']);
        $this->validator()->parseDate('data_fim', $data['data_fim']);

        $turma = $this->service->store($cursoId, $data);
        return $this->response()->created(['data' => $turma]);
    }

    public function update(int $id): ResponseInterface
    {
        $data = $this->request()->data();

        if (isset($data['status'])) {
            $this->validator()->parseEnum('status', $data['status'], StatusTurma::fromString(...));
        }
        if (isset($data['data_inicio'])) {
            $this->validator()->parseDate('data_inicio', $data['data_inicio']);
        }
        if (isset($data['data_fim'])) {
            $this->validator()->parseDate('data_fim', $data['data_fim']);
        }

        $turma = $this->service->update($id, $data);
        return $this->response()->success(['data' => $turma]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }

    public function index(): ResponseInterface
    {
        return $this->response()->success(['data' => $this->service->get()]);
    }

    public function indexByCurso(int $cursoId): ResponseInterface
    {
        return $this->response()->success(['data' => $this->service->get(['curso_id' => $cursoId])]);
    }
}
