<?php

namespace Tests\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\UsuarioServiceInterface;
use App\Entities\Usuario;
use App\Http\Controllers\UsuariosController;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;
use App\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

class UsuariosControllerTest extends TestCase
{
    private UsuarioServiceInterface $service;
    private UsuariosController $controller;

    protected function setUp(): void
    {
        $container = new Container();
        $container->singleton(ResponseInterface::class, fn() => new Response());
        $container->singleton(RequestInterface::class, fn() => new Request());
        Container::setContainer($container);

        $this->service    = $this->createMock(UsuarioServiceInterface::class);
        $this->controller = new UsuariosController($this->service);
    }

    private function makeUsuario(): Usuario
    {
        $u = new Usuario('João', new Email('joao@test.com'));
        $u->setId(1);
        return $u;
    }

    public function test_store_returns_201(): void
    {
        $this->service->method('store')->willReturn($this->makeUsuario());
        $response = $this->controller->store();
        $this->assertSame(201, $response->getStatusForTest());
    }

    public function test_destroy_returns_204(): void
    {
        $response = $this->controller->destroy(1);
        $this->assertSame(204, $response->getStatusForTest());
    }
}
