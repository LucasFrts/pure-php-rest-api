<?php
namespace Tests\Http\Controllers;

use App\Contracts\Services\CursoServiceInterface;
use App\Entities\Curso;
use App\Enums\Temas;
use App\Exceptions\Http\UnprocessableEntity;
use App\Http\Controllers\CursosController;

class CursosControllerTest extends ControllerTestCase
{
    private CursoServiceInterface $service;
    private CursosController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service    = $this->createMock(CursoServiceInterface::class);
        $this->controller = new CursosController($this->service);
    }

    private function makeCurso(int $id = 1): Curso
    {
        $c = new Curso('PHP', 'desc', Temas::Tecnologia, 'img.jpg');
        $c->setId($id);
        return $c;
    }

    public function test_index_returns_200_with_list(): void
    {
        $this->service->method('getAvailable')->willReturn([$this->makeCurso()]);
        $response = $this->controller->index();
        $this->assertSame(200, $response->getStatusForTest());
    }

    public function test_store_returns_201(): void
    {
        $this->withPostBody([
            'titulo'     => 'PHP',
            'descricao'  => 'desc',
            'tema'       => 'tecnologia',
            'url_imagem' => 'img.jpg',
        ]);
        $this->service->method('store')->willReturn($this->makeCurso());
        $response = $this->controller->store();
        $this->assertSame(201, $response->getStatusForTest());
    }

    public function test_store_throws_when_required_fields_missing(): void
    {
        $this->withPostBody(['titulo' => 'PHP']);
        $this->service->expects($this->never())->method('store');
        $this->expectException(UnprocessableEntity::class);
        $this->controller->store();
    }

    public function test_update_returns_200(): void
    {
        $this->withPostBody(['titulo' => 'Novo']);
        $this->service->method('update')->willReturn($this->makeCurso());
        $response = $this->controller->update(1);
        $this->assertSame(200, $response->getStatusForTest());
    }

    public function test_destroy_returns_204(): void
    {
        $response = $this->controller->destroy(1);
        $this->assertSame(204, $response->getStatusForTest());
    }
}
