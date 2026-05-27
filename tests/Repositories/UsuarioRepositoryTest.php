<?php

namespace Tests\Repositories;

use App\Entities\Usuario;
use App\Exceptions\Http\NotFound;
use App\Repositories\UsuarioRepository;
use App\ValueObjects\Email;
use PDO;
use PHPUnit\Framework\TestCase;

class UsuarioRepositoryTest extends TestCase
{
    private PDO $pdo;
    private UsuarioRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE
        )');
        $this->repo = new UsuarioRepository($this->pdo);
    }

    public function test_store_returns_usuario_with_id(): void
    {
        $u = $this->repo->store(['nome' => 'João', 'email' => 'joao@test.com']);
        $this->assertInstanceOf(Usuario::class, $u);
        $this->assertNotNull($u->getId());
        $this->assertSame('João', $u->getNome());
        $this->assertSame('joao@test.com', $u->getEmail()->getValue());
    }

    public function test_find_throws_for_unknown_id(): void
    {
        $this->expectException(NotFound::class);
        $this->repo->find(999);
    }

    public function test_destroy_removes_record(): void
    {
        $u = $this->repo->store(['nome' => 'Ana', 'email' => 'ana@test.com']);
        $this->repo->destroy($u->getId());
        $this->expectException(NotFound::class);
        $this->repo->find($u->getId());
    }
}
