<?php
namespace Tests\Http\Controllers;

use App\Contracts\Services\TurmaServiceInterface;
use App\Enums\StatusTurma;
use App\Entities\Turma;
use App\Http\Controllers\TurmasController;
use DateTime;

class TurmasControllerTest extends ControllerTestCase
{
    private TurmaServiceInterface $service;
    private TurmasController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service    = $this->createMock(TurmaServiceInterface::class);
        $this->controller = new TurmasController($this->service);
    }

    private function makeTurma(): Turma
    {
        $t = new Turma('T', 'desc', 30, StatusTurma::Disponivel,
            new DateTime('2026-06-01'), new DateTime('2026-12-01'), 1);
        $t->setId(1);
        return $t;
    }

    public function test_store_returns_201(): void
    {
        $this->withPostBody([
            'titulo'           => 'T',
            'descricao'        => 'desc',
            'quantidade_vagas' => 30,
            'status'           => 'disponivel',
            'data_inicio'      => '2026-06-01',
            'data_fim'         => '2026-12-01',
        ]);
        $this->service->method('store')->willReturn($this->makeTurma());
        $response = $this->controller->store(1);
        $this->assertSame(201, $response->getStatusForTest());
    }

    public function test_update_returns_200(): void
    {
        $this->service->method('update')->willReturn($this->makeTurma());
        $response = $this->controller->update(1);
        $this->assertSame(200, $response->getStatusForTest());
    }

    public function test_destroy_returns_204(): void
    {
        $response = $this->controller->destroy(1);
        $this->assertSame(204, $response->getStatusForTest());
    }

    public function test_index_returns_200_with_list(): void
    {
        $this->service->method('get')->willReturn([$this->makeTurma()]);
        $response = $this->controller->index();
        $this->assertSame(200, $response->getStatusForTest());
    }

    public function test_index_by_curso_returns_200_with_list(): void
    {
        $this->service->method('get')->willReturn([$this->makeTurma()]);
        $response = $this->controller->indexByCurso(1);
        $this->assertSame(200, $response->getStatusForTest());
    }
}
