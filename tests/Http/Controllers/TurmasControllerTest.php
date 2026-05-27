<?php
namespace Tests\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\TurmaServiceInterface;
use App\Entities\StatusTurma;
use App\Entities\Turma;
use App\Http\Controllers\TurmasController;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;
use DateTime;
use PHPUnit\Framework\TestCase;

class TurmasControllerTest extends TestCase
{
    private TurmaServiceInterface $service;
    private TurmasController $controller;

    protected function setUp(): void
    {
        $container = new Container();
        $container->singleton(ResponseInterface::class, fn() => new Response());
        $container->singleton(RequestInterface::class, fn() => new Request());
        Container::setContainer($container);

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
}
