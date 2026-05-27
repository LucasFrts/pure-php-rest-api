<?php

namespace Tests\Services;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Entities\Usuario;
use App\Exceptions\Http\NotFound;
use App\Exceptions\Http\UnprocessableEntity;
use App\Services\UsuarioService;
use App\ValueObjects\Email;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UsuarioServiceTest extends TestCase
{
    private UsuarioRepositoryInterface $repo;
    private LoggerInterface $logger;
    private UsuarioService $service;

    protected function setUp(): void
    {
        $this->repo    = $this->createMock(UsuarioRepositoryInterface::class);
        $this->logger  = $this->createMock(LoggerInterface::class);
        $this->service = new UsuarioService($this->repo, $this->logger);
    }

    private function makeUsuario(int $id = 1): Usuario
    {
        $u = new Usuario('João', new Email('joao@test.com'));
        $u->setId($id);
        return $u;
    }

    public function test_store_delegates_to_repo(): void
    {
        $data    = ['nome' => 'João', 'email' => 'joao@test.com'];
        $usuario = $this->makeUsuario();
        $this->repo->expects($this->once())->method('store')
            ->with($this->isInstanceOf(Usuario::class))
            ->willReturn($usuario);
        $this->assertSame($usuario, $this->service->store($data));
    }

    public function test_find_delegates_to_repo(): void
    {
        $usuario = $this->makeUsuario();
        $this->repo->method('find')->with(1)->willReturn($usuario);
        $this->assertSame($usuario, $this->service->find(1));
    }

    public function test_find_propagates_not_found(): void
    {
        $this->repo->method('find')->willThrowException(new NotFound());
        $this->expectException(NotFound::class);
        $this->service->find(99);
    }

    public function test_update_delegates_to_repo(): void
    {
        $existing = $this->makeUsuario();
        $updated  = $this->makeUsuario();
        $data     = ['nome' => 'Maria', 'email' => 'maria@test.com'];
        $this->repo->expects($this->once())->method('find')->with(1)->willReturn($existing);
        $this->repo->expects($this->once())->method('update')
            ->with(1, $this->isInstanceOf(Usuario::class))
            ->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, $data));
    }

    public function test_update_partial_falls_back_to_existing_fields(): void
    {
        $existing = $this->makeUsuario();
        $updated  = $this->makeUsuario();
        $this->repo->method('find')->with(1)->willReturn($existing);
        $this->repo->expects($this->once())->method('update')
            ->with(1, $this->callback(function (Usuario $u) use ($existing) {
                return $u->getNome()  === 'Novo Nome'
                    && $u->getEmail() === $existing->getEmail();
            }))
            ->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, ['nome' => 'Novo Nome']));
    }

    public function test_store_throws_unprocessable_for_invalid_email(): void
    {
        $this->expectException(UnprocessableEntity::class);
        $this->service->store(['nome' => 'João', 'email' => 'not-an-email']);
    }

    public function test_update_throws_unprocessable_for_invalid_email(): void
    {
        $existing = $this->makeUsuario();
        $this->repo->method('find')->with(1)->willReturn($existing);
        $this->expectException(UnprocessableEntity::class);
        $this->service->update(1, ['email' => 'not-an-email']);
    }

    public function test_destroy_delegates_to_repo(): void
    {
        $this->repo->expects($this->once())->method('destroy')->with(1);
        $this->service->destroy(1);
    }

    public function test_store_logs_info_on_success(): void
    {
        $usuario = $this->makeUsuario(8);
        $this->repo->method('store')->willReturn($usuario);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('UsuarioService::store usuarioId=8'));
        $this->service->store(['nome' => 'João', 'email' => 'joao@test.com']);
    }

    public function test_store_logs_error_on_invalid_email(): void
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('UsuarioService::store FAILED:'));
        $this->expectException(UnprocessableEntity::class);
        $this->service->store(['nome' => 'João', 'email' => 'not-an-email']);
    }

    public function test_update_logs_info_on_success(): void
    {
        $existing = $this->makeUsuario(4);
        $updated  = $this->makeUsuario(4);
        $this->repo->method('find')->willReturn($existing);
        $this->repo->method('update')->willReturn($updated);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('UsuarioService::update usuarioId=4'));
        $this->service->update(4, ['nome' => 'Maria']);
    }

    public function test_update_logs_error_on_invalid_email(): void
    {
        $existing = $this->makeUsuario(2);
        $this->repo->method('find')->willReturn($existing);
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('UsuarioService::update FAILED:'));
        $this->expectException(UnprocessableEntity::class);
        $this->service->update(2, ['email' => 'not-an-email']);
    }

    public function test_destroy_logs_info_on_success(): void
    {
        $this->repo->expects($this->once())->method('destroy')->with(3);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('UsuarioService::destroy usuarioId=3'));
        $this->service->destroy(3);
    }
}
