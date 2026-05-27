<?php

namespace Tests\Integration;

class CursosIntegrationTest extends IntegrationTestCase
{
    private function cursosPayload(string $titulo = 'PHP Avançado'): array
    {
        return [
            'titulo'     => $titulo,
            'descricao'  => 'Aprenda PHP moderno',
            'tema'       => 'tecnologia',
            'url_imagem' => 'https://example.com/img.jpg',
        ];
    }

    public function test_index_returns_200_with_empty_list(): void
    {
        $r = $this->dispatch('GET', '/api/v1/cursos');
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertSame([], $r->getDataForTest()['data']);
    }

    public function test_store_returns_201_with_created_curso(): void
    {
        $r = $this->dispatch('POST', '/api/v1/cursos', $this->cursosPayload());
        $this->assertSame(201, $r->getStatusForTest());
        $data = $r->getDataForTest()['data'];
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('PHP Avançado', $data['titulo']);
    }

    public function test_index_returns_created_curso(): void
    {
        $this->dispatch('POST', '/api/v1/cursos', $this->cursosPayload());
        $r = $this->dispatch('GET', '/api/v1/cursos');
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertCount(1, $r->getDataForTest()['data']);
    }

    public function test_update_returns_200_with_updated_data(): void
    {
        $created = $this->dispatch('POST', '/api/v1/cursos', $this->cursosPayload());
        $id = $created->getDataForTest()['data']['id'];

        $r = $this->dispatch('PUT', "/api/v1/cursos/{$id}", ['titulo' => 'Go Avançado']);
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertSame('Go Avançado', $r->getDataForTest()['data']['titulo']);
    }

    public function test_destroy_returns_204(): void
    {
        $created = $this->dispatch('POST', '/api/v1/cursos', $this->cursosPayload());
        $id = $created->getDataForTest()['data']['id'];

        $r = $this->dispatch('DELETE', "/api/v1/cursos/{$id}");
        $this->assertSame(204, $r->getStatusForTest());
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $r = $this->dispatch('PUT', '/api/v1/cursos/999', ['titulo' => 'X']);
        $this->assertSame(404, $r->getStatusForTest());
    }

    public function test_destroy_nonexistent_returns_404(): void
    {
        $r = $this->dispatch('DELETE', '/api/v1/cursos/999');
        $this->assertSame(404, $r->getStatusForTest());
    }
}
