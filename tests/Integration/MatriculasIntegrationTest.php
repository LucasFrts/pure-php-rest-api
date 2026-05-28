<?php

namespace Tests\Integration;

class MatriculasIntegrationTest extends IntegrationTestCase
{
    private int $usuarioId;
    private int $turmaId;

    protected function setUp(): void
    {
        parent::setUp();

        $curso = $this->dispatch('POST', '/api/v1/cursos', [
            'titulo'     => 'Curso',
            'descricao'  => 'desc',
            'tema'       => 'tecnologia',
            'url_imagem' => 'img.jpg',
        ]);
        $cursoId = $curso->getDataForTest()['data']['id'];

        $turma = $this->dispatch('POST', "/api/v1/cursos/{$cursoId}/turmas", [
            'titulo'           => 'Turma',
            'descricao'        => 'desc',
            'quantidade_vagas' => 10,
            'status'           => 'disponivel',
            'data_inicio'      => '2026-01-01',
            'data_fim'         => '2027-12-01',
        ]);
        $this->turmaId = $turma->getDataForTest()['data']['id'];

        $usuario = $this->dispatch('POST', '/api/v1/usuarios', [
            'nome'  => 'Alice',
            'email' => 'alice@example.com',
        ]);
        $this->usuarioId = $usuario->getDataForTest()['data']['id'];
    }

    public function test_store_returns_201(): void
    {
        $r = $this->dispatch('POST', '/api/v1/matriculas', [
            'usuario_id' => $this->usuarioId,
            'turma_id'   => $this->turmaId,
        ]);
        $this->assertSame(201, $r->getStatusForTest());
        $this->assertArrayHasKey('id', $r->getDataForTest()['data']);
    }

    public function test_index_by_usuario_returns_matriculas(): void
    {
        $this->dispatch('POST', '/api/v1/matriculas', [
            'usuario_id' => $this->usuarioId,
            'turma_id'   => $this->turmaId,
        ]);

        $r = $this->dispatch('GET', "/api/v1/usuarios/{$this->usuarioId}/matriculas");
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertCount(1, $r->getDataForTest()['data']);
    }

    public function test_destroy_returns_204(): void
    {
        $created = $this->dispatch('POST', '/api/v1/matriculas', [
            'usuario_id' => $this->usuarioId,
            'turma_id'   => $this->turmaId,
        ]);
        $id = $created->getDataForTest()['data']['id'];

        $r = $this->dispatch('DELETE', "/api/v1/matriculas/{$id}");
        $this->assertSame(204, $r->getStatusForTest());
    }

    public function test_store_with_nonexistent_turma_returns_404(): void
    {
        $r = $this->dispatch('POST', '/api/v1/matriculas', [
            'usuario_id' => $this->usuarioId,
            'turma_id'   => 9999,
        ]);
        $this->assertSame(404, $r->getStatusForTest());
    }
}
