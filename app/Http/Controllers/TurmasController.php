<?php
namespace App\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\TurmaServiceInterface;
use App\Entities\Turma;
use App\Support\Container;

class TurmasController extends BaseController
{
    public function __construct(private TurmaServiceInterface $service)
    {
    }

    public function store(int $cursoId): ResponseInterface
    {
        $request = Container::getContainer()->get(RequestInterface::class);
        $data    = $request->getJSON();
        $turma   = $this->service->store($cursoId, $data);
        return $this->response()->created(['data' => $this->serializeTurma($turma)]);
    }

    public function update(int $id): ResponseInterface
    {
        $request = Container::getContainer()->get(RequestInterface::class);
        $data    = $request->getJSON();
        $turma   = $this->service->update($id, $data);
        return $this->response()->success(['data' => $this->serializeTurma($turma)]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }

    private function serializeTurma(Turma $turma): array
    {
        return [
            'id'               => $turma->getId(),
            'curso_id'         => $turma->getCursoId(),
            'titulo'           => $turma->getTitulo(),
            'descricao'        => $turma->getDescricao(),
            'quantidade_vagas' => $turma->getQuantidadeVagas(),
            'status'           => $turma->getStatus()->toString(),
            'data_inicio'      => $turma->getDataInicio()->format('Y-m-d'),
            'data_fim'         => $turma->getDataFim()->format('Y-m-d'),
        ];
    }
}
