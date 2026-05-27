<?php

namespace Tests\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\MatriculaServiceInterface;
use App\Entities\Matricula;
use App\Entities\StatusMatricula;
use App\Http\Controllers\MatriculasController;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;
use PHPUnit\Framework\TestCase;

class MatriculasControllerTest extends TestCase
{
    private MatriculaServiceInterface $service;
    private MatriculasController $controller;

    protected function setUp(): void
    {
        $container = new Container();
        $container->singleton(ResponseInterface::class, fn() => new Response());
        $container->singleton(RequestInterface::class, fn() => new Request());
        Container::setContainer($container);

        $this->service    = $this->createMock(MatriculaServiceInterface::class);
        $this->controller = new MatriculasController($this->service);
    }

    public function test_store_returns_201(): void
    {
        $m = new Matricula(1, 2, 3);
        $m->setId(1);
        $this->service->method('enroll')->willReturn($m);
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

        $container = new Container();
        $container->singleton(ResponseInterface::class, fn() => new Response());
        $container->singleton(RequestInterface::class, fn() => $mockRequest);
        Container::setContainer($container);

        $controller = new MatriculasController($this->service);

        $this->service->expects($this->once())->method('updateStatus')->with(1, 'inativo')->willReturn($m);

        $response = $controller->updateStatus(1);
        $this->assertSame(200, $response->getStatusForTest());
    }
}
