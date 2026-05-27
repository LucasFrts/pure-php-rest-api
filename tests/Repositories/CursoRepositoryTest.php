<?php

namespace Tests\Repositories;

use App\Entities\Curso;
use App\Entities\Temas;
use App\Repositories\CursoRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class CursoRepositoryTest extends TestCase
{
    private PDO $pdo;
    private CursoRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE cursos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            descricao TEXT NOT NULL,
            tema TEXT NOT NULL,
            url_imagem TEXT NOT NULL
        )');
        $this->pdo->exec('CREATE TABLE turmas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            curso_id INTEGER NOT NULL,
            titulo TEXT NOT NULL,
            descricao TEXT NOT NULL,
            quantidade_vagas INTEGER NOT NULL,
            status TEXT NOT NULL,
            data_inicio TEXT NOT NULL,
            data_fim TEXT NOT NULL
        )');
        $this->repo = new CursoRepository($this->pdo);
    }

    public function test_store_returns_curso_with_id(): void
    {
        $curso = $this->repo->store([
            'titulo'     => 'PHP Avançado',
            'descricao'  => 'Aprenda PHP',
            'tema'       => 'tecnologia',
            'url_imagem' => 'https://img.jpg',
        ]);
        $this->assertInstanceOf(Curso::class, $curso);
        $this->assertNotNull($curso->getId());
        $this->assertSame('PHP Avançado', $curso->getTitulo());
        $this->assertSame(Temas::Tecnologia, $curso->getTema());
    }

    public function test_find_returns_stored_curso(): void
    {
        $stored = $this->repo->store([
            'titulo' => 'Marketing Digital', 'descricao' => 'desc',
            'tema' => 'marketing', 'url_imagem' => 'img.jpg',
        ]);
        $found = $this->repo->find($stored->getId());
        $this->assertSame($stored->getId(), $found->getId());
        $this->assertSame('Marketing Digital', $found->getTitulo());
    }

    public function test_find_throws_for_unknown_id(): void
    {
        $this->expectException(\App\Exceptions\Http\NotFound::class);
        $this->repo->find(999);
    }

    public function test_update_changes_fields(): void
    {
        $curso = $this->repo->store([
            'titulo' => 'Antigo', 'descricao' => 'desc',
            'tema' => 'agro', 'url_imagem' => 'img.jpg',
        ]);
        $updated = $this->repo->update($curso->getId(), [
            'titulo' => 'Novo', 'descricao' => 'nova desc',
            'tema' => 'inovacao', 'url_imagem' => 'new.jpg',
        ]);
        $this->assertSame('Novo', $updated->getTitulo());
        $this->assertSame(Temas::Inovacao, $updated->getTema());
    }

    public function test_destroy_removes_record(): void
    {
        $curso = $this->repo->store([
            'titulo' => 'Delete Me', 'descricao' => 'desc',
            'tema' => 'agro', 'url_imagem' => 'img.jpg',
        ]);
        $this->repo->destroy($curso->getId());
        $this->expectException(\App\Exceptions\Http\NotFound::class);
        $this->repo->find($curso->getId());
    }

    public function test_get_with_available_turmas_returns_matching_cursos(): void
    {
        $curso = $this->repo->store([
            'titulo' => 'Agro Futuro', 'descricao' => 'desc',
            'tema' => 'agro', 'url_imagem' => 'img.jpg',
        ]);
        $past  = date('Y-m-d', strtotime('-10 days'));
        $future = date('Y-m-d', strtotime('+10 days'));
        $this->pdo->exec("INSERT INTO turmas VALUES (
            NULL, {$curso->getId()}, 'T1', 'desc', 10, 'disponivel', '{$past}', '{$future}'
        )");
        $results = $this->repo->getWithAvailableTurmas([]);
        $this->assertCount(1, $results);
        $this->assertSame($curso->getId(), $results[0]->getId());
    }

    public function test_get_with_available_turmas_filters_by_titulo(): void
    {
        $c1 = $this->repo->store(['titulo' => 'PHP Dev', 'descricao' => 'd', 'tema' => 'tecnologia', 'url_imagem' => 'i.jpg']);
        $c2 = $this->repo->store(['titulo' => 'Marketing Pro', 'descricao' => 'd', 'tema' => 'marketing', 'url_imagem' => 'i.jpg']);
        $past = date('Y-m-d', strtotime('-10 days'));
        $future = date('Y-m-d', strtotime('+10 days'));
        $this->pdo->exec("INSERT INTO turmas VALUES (NULL, {$c1->getId()}, 'T', 'd', 10, 'disponivel', '{$past}', '{$future}')");
        $this->pdo->exec("INSERT INTO turmas VALUES (NULL, {$c2->getId()}, 'T', 'd', 10, 'disponivel', '{$past}', '{$future}')");

        $results = $this->repo->getWithAvailableTurmas(['titulo' => 'PHP']);
        $this->assertCount(1, $results);
        $this->assertSame('PHP Dev', $results[0]->getTitulo());
    }

    public function test_get_with_available_turmas_filters_by_tema(): void
    {
        $c1 = $this->repo->store(['titulo' => 'A', 'descricao' => 'd', 'tema' => 'tecnologia', 'url_imagem' => 'i.jpg']);
        $c2 = $this->repo->store(['titulo' => 'B', 'descricao' => 'd', 'tema' => 'agro', 'url_imagem' => 'i.jpg']);
        $past = date('Y-m-d', strtotime('-10 days'));
        $future = date('Y-m-d', strtotime('+10 days'));
        $this->pdo->exec("INSERT INTO turmas VALUES (NULL, {$c1->getId()}, 'T', 'd', 10, 'disponivel', '{$past}', '{$future}')");
        $this->pdo->exec("INSERT INTO turmas VALUES (NULL, {$c2->getId()}, 'T', 'd', 10, 'disponivel', '{$past}', '{$future}')");

        $results = $this->repo->getWithAvailableTurmas(['tema' => 'agro']);
        $this->assertCount(1, $results);
        $this->assertSame('B', $results[0]->getTitulo());
    }
}
