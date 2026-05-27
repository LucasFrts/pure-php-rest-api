<?php

namespace App\Http\Controllers;

use App\Contracts\ResponseInterface;
use App\Contracts\Services\TurmaServiceInterface;

class TurmasController extends BaseController
{
    public function __construct(private TurmaServiceInterface $service)
    {
    }

    public function store(int $cursoId): ResponseInterface
    {
        $turma = $this->service->store($cursoId, $this->request()->data());
        return $this->response()->created(['data' => $turma]);
    }

    public function update(int $id): ResponseInterface
    {
        $turma = $this->service->update($id, $this->request()->data());
        return $this->response()->success(['data' => $turma]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }
}
