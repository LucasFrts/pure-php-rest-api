<?php

namespace Tests\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Entities\Curso;
use App\Entities\Temas;
use App\Exceptions\Http\NotFound;
use App\Services\CursoService;
use PHPUnit\Framework\TestCase;

class CursoServiceTest extends TestCase
{
    private CursoRepositoryInterface $repo;
    private CursoService $service;

    protected function setUp(): void
    {
        $this->repo    = $this->createMock(CursoRepositoryInterface::class);
        $this->service = new CursoService($this->repo);
    }

    private function makeCurso(int $id = 1): Curso
    {
        $c = new Curso('PHP', 'desc', Temas::Tecnologia, 'img.jpg');
        $c->setId($id);
        return $c;
    }

    public function test_get_available_delegates_to_repo(): void
    {
        $filters  = ['titulo' => 'PHP'];
        $expected = [$this->makeCurso()];
        $this->repo->expects($this->once())
            ->method('getWithAvailableTurmas')
            ->with($filters)
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getAvailable($filters));
    }

    public function test_find_delegates_to_repo(): void
    {
        $curso = $this->makeCurso();
        $this->repo->method('find')->with(1)->willReturn($curso);
        $this->assertSame($curso, $this->service->find(1));
    }

    public function test_find_propagates_not_found(): void
    {
        $this->repo->method('find')->willThrowException(new NotFound());
        $this->expectException(NotFound::class);
        $this->service->find(99);
    }

    public function test_store_delegates_to_repo(): void
    {
        $data  = ['titulo' => 'PHP', 'descricao' => 'd', 'tema' => 'tecnologia', 'url_imagem' => 'i.jpg'];
        $curso = $this->makeCurso();
        $this->repo->expects($this->once())->method('store')
            ->with($this->isInstanceOf(Curso::class))
            ->willReturn($curso);
        $this->assertSame($curso, $this->service->store($data));
    }

    public function test_update_delegates_to_repo(): void
    {
        $data     = ['titulo' => 'New', 'descricao' => 'd', 'tema' => 'agro', 'url_imagem' => 'i.jpg'];
        $existing = $this->makeCurso();
        $updated  = $this->makeCurso();
        $this->repo->expects($this->once())->method('find')->with(1)->willReturn($existing);
        $this->repo->expects($this->once())->method('update')
            ->with(1, $this->isInstanceOf(Curso::class))
            ->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, $data));
    }

    public function test_update_partial_falls_back_to_existing_fields(): void
    {
        $existing = $this->makeCurso();
        $updated  = $this->makeCurso();
        $this->repo->method('find')->with(1)->willReturn($existing);
        $this->repo->expects($this->once())->method('update')
            ->with(1, $this->callback(function (Curso $c) use ($existing) {
                return $c->getTitulo()    === 'Novo Titulo'
                    && $c->getDescricao() === $existing->getDescricao()
                    && $c->getTema()      === $existing->getTema()
                    && $c->getUrlImagem() === $existing->getUrlImagem();
            }))
            ->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, ['titulo' => 'Novo Titulo']));
    }

    public function test_destroy_delegates_to_repo(): void
    {
        $this->repo->expects($this->once())->method('destroy')->with(1);
        $this->service->destroy(1);
    }
}
