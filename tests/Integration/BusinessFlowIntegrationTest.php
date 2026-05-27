<?php

namespace Tests\Integration;

class BusinessFlowIntegrationTest extends IntegrationTestCase
{
    public function test_full_enrollment_and_turma_closure_inactivates_matricula(): void
    {
        // 1. Create curso
        $r = $this->dispatch('POST', '/api/v1/cursos', [
            'titulo'     => 'PHP Avançado',
            'descricao'  => 'Curso completo de PHP',
            'tema'       => 'tecnologia',
            'url_imagem' => 'https://example.com/php.jpg',
        ]);
        $this->assertSame(201, $r->getStatusForTest());
        $cursoId = $r->getDataForTest()['data']['id'];

        // 2. Create turma under the curso
        $r = $this->dispatch('POST', "/api/v1/cursos/{$cursoId}/turmas", [
            'titulo'           => 'Turma Janeiro',
            'descricao'        => 'Turma do primeiro semestre',
            'quantidade_vagas' => 30,
            'status'           => 'disponivel',
            'data_inicio'      => '2026-01-01',
            'data_fim'         => '2026-06-30',
        ]);
        $this->assertSame(201, $r->getStatusForTest());
        $turmaId = $r->getDataForTest()['data']['id'];

        // 3. Create usuario
        $r = $this->dispatch('POST', '/api/v1/usuarios', [
            'nome'  => 'João Silva',
            'email' => 'joao@example.com',
        ]);
        $this->assertSame(201, $r->getStatusForTest());
        $usuarioId = $r->getDataForTest()['data']['id'];

        // 4. Matriculate usuario in turma
        $r = $this->dispatch('POST', '/api/v1/matriculas', [
            'usuario_id' => $usuarioId,
            'turma_id'   => $turmaId,
        ]);
        $this->assertSame(201, $r->getStatusForTest());
        $matriculaStatus = $r->getDataForTest()['data']['status'];
        $this->assertSame('ativo', $matriculaStatus);

        // 5. Encerrar turma — must inactivate all matriculas
        $r = $this->dispatch('PUT', "/api/v1/turmas/{$turmaId}", [
            'titulo'           => 'Turma Janeiro',
            'descricao'        => 'Turma do primeiro semestre',
            'quantidade_vagas' => 30,
            'status'           => 'encerrado',
            'data_inicio'      => '2026-01-01',
            'data_fim'         => '2026-06-30',
        ]);
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertSame('encerrado', $r->getDataForTest()['data']['status']);

        // 6. Verify matricula is now inativo
        $r = $this->dispatch('GET', "/api/v1/usuarios/{$usuarioId}/matriculas");
        $this->assertSame(200, $r->getStatusForTest());
        $matriculas = $r->getDataForTest()['data'];
        $this->assertCount(1, $matriculas);
        $this->assertSame('inativo', $matriculas[0]['status']);
    }

    public function test_listing_endpoints_reflect_correct_data(): void
    {
        // Create 2 cursos, 1 turma each, 1 usuario — verify listings
        $this->dispatch('POST', '/api/v1/cursos', [
            'titulo' => 'Curso A', 'descricao' => 'desc', 'tema' => 'tecnologia', 'url_imagem' => 'a.jpg',
        ]);
        $rB = $this->dispatch('POST', '/api/v1/cursos', [
            'titulo' => 'Curso B', 'descricao' => 'desc', 'tema' => 'tecnologia', 'url_imagem' => 'b.jpg',
        ]);
        $cursoBId = $rB->getDataForTest()['data']['id'];

        $this->dispatch('POST', '/api/v1/usuarios', ['nome' => 'Maria', 'email' => 'maria@example.com']);

        // Verify listings count correctly
        $this->assertCount(2, $this->dispatch('GET', '/api/v1/cursos')->getDataForTest()['data']);
        $this->assertCount(1, $this->dispatch('GET', '/api/v1/usuarios')->getDataForTest()['data']);
        $this->assertCount(0, $this->dispatch('GET', '/api/v1/turmas')->getDataForTest()['data']);

        // Add turma to Curso B and verify scoped listing
        $this->dispatch('POST', "/api/v1/cursos/{$cursoBId}/turmas", [
            'titulo' => 'T1', 'descricao' => 'desc', 'quantidade_vagas' => 5,
            'status' => 'disponivel', 'data_inicio' => '2026-07-01', 'data_fim' => '2026-12-31',
        ]);
        $this->assertCount(1, $this->dispatch('GET', '/api/v1/turmas')->getDataForTest()['data']);
        $this->assertCount(1, $this->dispatch('GET', "/api/v1/cursos/{$cursoBId}/turmas")->getDataForTest()['data']);
    }
}
