<?php

namespace Tests\Integration;

class TurmasIntegrationTest extends IntegrationTestCase
{
    private int $cursoId;

    protected function setUp(): void
    {
        parent::setUp();
        $r = $this->dispatch('POST', '/api/v1/cursos', [
            'titulo'     => 'Curso Base',
            'descricao'  => 'desc',
            'tema'       => 'tecnologia',
            'imagem_url' => 'img.jpg',
        ]);
        $this->cursoId = $r->getDataForTest()['data']['id'];
    }

    private function turmaPayload(): array
    {
        return [
            'titulo'           => 'Turma 1',
            'descricao'        => 'desc turma',
            'quantidade_vagas' => 30,
            'status'           => 'disponivel',
            'data_inicio'      => '2026-06-01',
            'data_fim'         => '2026-12-01',
        ];
    }

    public function test_store_returns_201(): void
    {
        $r = $this->dispatch('POST', "/api/v1/cursos/{$this->cursoId}/turmas", $this->turmaPayload());
        $this->assertSame(201, $r->getStatusForTest());
        $data = $r->getDataForTest()['data'];
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('Turma 1', $data['titulo']);
    }

    public function test_index_returns_all_turmas(): void
    {
        $this->dispatch('POST', "/api/v1/cursos/{$this->cursoId}/turmas", $this->turmaPayload());
        $r = $this->dispatch('GET', '/api/v1/turmas');
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertCount(1, $r->getDataForTest()['data']);
    }

    public function test_index_by_curso_returns_only_curso_turmas(): void
    {
        // Create turma under our curso
        $this->dispatch('POST', "/api/v1/cursos/{$this->cursoId}/turmas", $this->turmaPayload());

        // Create a second curso and its turma
        $r2 = $this->dispatch('POST', '/api/v1/cursos', [
            'titulo'     => 'Outro Curso',
            'descricao'  => 'desc',
            'tema'       => 'tecnologia',
            'imagem_url' => 'img.jpg',
        ]);
        $outroCursoId = $r2->getDataForTest()['data']['id'];
        $this->dispatch('POST', "/api/v1/cursos/{$outroCursoId}/turmas", array_merge($this->turmaPayload(), ['titulo' => 'Turma Outro']));

        // Index by first curso must return only 1 turma
        $r = $this->dispatch('GET', "/api/v1/cursos/{$this->cursoId}/turmas");
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertCount(1, $r->getDataForTest()['data']);
        $this->assertSame('Turma 1', $r->getDataForTest()['data'][0]['titulo']);
    }

    public function test_update_returns_200(): void
    {
        $created = $this->dispatch('POST', "/api/v1/cursos/{$this->cursoId}/turmas", $this->turmaPayload());
        $id = $created->getDataForTest()['data']['id'];

        $r = $this->dispatch('PUT', "/api/v1/turmas/{$id}", array_merge($this->turmaPayload(), ['titulo' => 'Turma Atualizada']));
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertSame('Turma Atualizada', $r->getDataForTest()['data']['titulo']);
    }

    public function test_destroy_returns_204(): void
    {
        $created = $this->dispatch('POST', "/api/v1/cursos/{$this->cursoId}/turmas", $this->turmaPayload());
        $id = $created->getDataForTest()['data']['id'];

        $r = $this->dispatch('DELETE', "/api/v1/turmas/{$id}");
        $this->assertSame(204, $r->getStatusForTest());
    }

    public function test_store_with_nonexistent_curso_returns_404(): void
    {
        $r = $this->dispatch('POST', '/api/v1/cursos/9999/turmas', $this->turmaPayload());
        $this->assertSame(404, $r->getStatusForTest());
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $r = $this->dispatch('PUT', '/api/v1/turmas/9999', $this->turmaPayload());
        $this->assertSame(404, $r->getStatusForTest());
    }
}
