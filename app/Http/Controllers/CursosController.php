<?php

namespace App\Http\Controllers;

use App\Contracts\ResponseInterface;
use App\Contracts\Services\CursoServiceInterface;

class CursosController extends BaseController
{
    public function __construct(private CursoServiceInterface $service)
    {
    }

    public function index(): ResponseInterface
    {
        $filters = array_filter([
            'titulo' => $this->request()->query('titulo'),
            'tema'   => $this->request()->query('tema'),
        ]);
        return $this->response()->success(['data' => $this->service->getAvailable($filters)]);
    }

    public function store(): ResponseInterface
    {
        $curso = $this->service->store($this->request()->data());
        return $this->response()->created(['data' => $curso]);
    }

    public function update(int $id): ResponseInterface
    {
        $curso = $this->service->update($id, $this->request()->data());
        return $this->response()->success(['data' => $curso]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }
}
