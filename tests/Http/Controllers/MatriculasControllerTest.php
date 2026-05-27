<?php

namespace Tests\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\MatriculaServiceInterface;
use App\Entities\Matricula;
use App\Enums\StatusMatricula;
use App\Exceptions\Http\UnprocessableEntity;
use App\Http\Controllers\MatriculasController;
use App\Support\Request;

class MatriculasControllerTest extends ControllerTestCase
{
    private MatriculaServiceInterface $service;
    private MatriculasController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service    = $this->createMock(MatriculaServiceInterface::class);
        $this->controller = new MatriculasController($this->service);
    }

    public function test_store_returns_201(): void
    {
        $this->withPostBody(['usuario_id' => 1, 'turma_id' => 2]);

        $m = new Matricula(1, 2, 3);
        $m->setId(1);
        $this->service->method('enroll')->with(1, 2)->willReturn($m);
        $response = $this->controller->store();
        $this->assertSame(201, $response->getStatusForTest());
    }

    public function test_index_returns_200_with_list(): void
    {
        $this->service->method('getByUsuario')->with(1)->willReturn([]);
        $response = $this->controller->index(1);
        $this->assertSame(200, $response->getStatusForTest());
    }

    public function test_destroy_returns_204(): void
    {
        $response = $this->controller->destroy(1);
        $this->assertSame(204, $response->getStatusForTest());
    }

    public function test_update_status_returns_200_with_updated_matricula(): void
    {
        $m = new Matricula(1, 2, 3);
        $m->setId(1);
        $m->setStatus(StatusMatricula::Inativo);

        $mockRequest = $this->createMock(RequestInterface::class);
        // Use expects syntax to avoid the method() name collision
        $mockRequest->expects($this->atLeast(0))->method('data')->willReturn(['status' => 'inativo']);

        $this->container->forgetInstance(RequestInterface::class);
        $this->container->singleton(RequestInterface::class, fn() => $mockRequest);

        $controller = new MatriculasController($this->service);

        $this->service->expects($this->once())->method('updateStatus')->with(1, 'inativo')->willReturn($m);

        $response = $controller->updateStatus(1);
        $this->assertSame(200, $response->getStatusForTest());
    }

    public function test_update_status_throws_when_status_missing(): void
    {
        $this->expectException(UnprocessableEntity::class);

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->atLeast(0))->method('data')->willReturn([]);

        $this->container->forgetInstance(RequestInterface::class);
        $this->container->singleton(RequestInterface::class, fn() => $mockRequest);

        $controller = new MatriculasController($this->service);

        $controller->updateStatus(1);
    }
}
