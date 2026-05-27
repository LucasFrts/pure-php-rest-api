<?php

namespace App\Http\Controllers;

use App\Contracts\ResponseInterface;
use App\Contracts\Services\CursoServiceInterface;
use App\Entities\Curso;

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
        $cursos = $this->service->getAvailable($filters);
        return $this->response()->success(['data' => array_map([$this, 'serializeCurso'], $cursos)]);
    }

    public function store(): ResponseInterface
    {
        $data  = $this->request()->getJSON();
        $curso = $this->service->store($data);
        return $this->response()->created(['data' => $this->serializeCurso($curso)]);
    }

    public function update(int $id): ResponseInterface
    {
        $data  = $this->request()->getJSON();
        $curso = $this->service->update($id, $data);
        return $this->response()->success(['data' => $this->serializeCurso($curso)]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }

    private function serializeCurso(Curso $curso): array
    {
        return [
            'id'         => $curso->getId(),
            'titulo'     => $curso->getTitulo(),
            'descricao'  => $curso->getDescricao(),
            'tema'       => $curso->getTema()->toString(),
            'url_imagem' => $curso->getUrlImagem(),
        ];
    }
}
