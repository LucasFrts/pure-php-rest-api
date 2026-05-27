<?php

namespace App\Http\Controllers;

use App\Contracts\ResponseInterface;
use App\Contracts\Services\CursoServiceInterface;
use App\Enums\Temas;

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
        return $this->response()->success(['data' => $this->service->get($filters)]);
    }

    public function store(): ResponseInterface
    {
        $data = $this->request()->data();
        $this->validator()->requireFields($data, ['titulo', 'descricao', 'tema', 'url_imagem']);
        $this->validator()->parseEnum('tema', $data['tema'], Temas::fromString(...));

        $curso = $this->service->store($data);
        return $this->response()->created(['data' => $curso]);
    }

    public function update(int $id): ResponseInterface
    {
        $data = $this->request()->data();

        if (isset($data['tema'])) {
            $this->validator()->parseEnum('tema', $data['tema'], Temas::fromString(...));
        }

        $curso = $this->service->update($id, $data);
        return $this->response()->success(['data' => $curso]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }
}
