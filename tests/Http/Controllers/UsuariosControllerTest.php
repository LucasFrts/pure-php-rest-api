<?php

namespace Tests\Http\Controllers;

use App\Contracts\Services\UsuarioServiceInterface;
use App\Entities\Usuario;
use App\Http\Controllers\UsuariosController;
use App\ValueObjects\Email;

class UsuariosControllerTest extends ControllerTestCase
{
    private UsuarioServiceInterface $service;
    private UsuariosController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service    = $this->createMock(UsuarioServiceInterface::class);
        $this->controller = new UsuariosController($this->service);
    }

    private function makeUsuario(int $id = 1): Usuario
    {
        $u = new Usuario('Alice', new Email('alice@example.com'));
        $u->setId($id);
        return $u;
    }

    public function test_index_returns_200_with_list(): void
    {
        $this->service->method('get')->willReturn([$this->makeUsuario()]);
        $response = $this->controller->index();
        $this->assertSame(200, $response->getStatusForTest());
    }

    public function test_store_returns_201(): void
    {
        $this->withPostBody(['nome' => 'Alice', 'email' => 'alice@example.com']);
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
