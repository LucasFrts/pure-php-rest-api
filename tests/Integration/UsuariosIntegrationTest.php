<?php

namespace Tests\Integration;

class UsuariosIntegrationTest extends IntegrationTestCase
{
    private function usuariosPayload(string $email = 'alice@example.com'): array
    {
        return [
            'nome'  => 'Alice',
            'email' => $email,
        ];
    }

    public function test_index_returns_200_with_empty_list(): void
    {
        $r = $this->dispatch('GET', '/api/v1/usuarios');
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertSame([], $r->getDataForTest()['data']);
    }

    public function test_store_returns_201_with_created_usuario(): void
    {
        $r = $this->dispatch('POST', '/api/v1/usuarios', $this->usuariosPayload());
        $this->assertSame(201, $r->getStatusForTest());
        $data = $r->getDataForTest()['data'];
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('Alice', $data['nome']);
        $this->assertSame('alice@example.com', $data['email']);
    }

    public function test_index_returns_created_usuario(): void
    {
        $this->dispatch('POST', '/api/v1/usuarios', $this->usuariosPayload());
        $r = $this->dispatch('GET', '/api/v1/usuarios');
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertCount(1, $r->getDataForTest()['data']);
    }

    public function test_destroy_returns_204(): void
    {
        $created = $this->dispatch('POST', '/api/v1/usuarios', $this->usuariosPayload());
        $id = $created->getDataForTest()['data']['id'];

        $r = $this->dispatch('DELETE', "/api/v1/usuarios/{$id}");
        $this->assertSame(204, $r->getStatusForTest());
    }

    public function test_store_duplicate_email_returns_422(): void
    {
        $this->dispatch('POST', '/api/v1/usuarios', $this->usuariosPayload());
        $r = $this->dispatch('POST', '/api/v1/usuarios', $this->usuariosPayload());
        $this->assertSame(422, $r->getStatusForTest());
    }

    public function test_store_invalid_email_returns_422(): void
    {
        $r = $this->dispatch('POST', '/api/v1/usuarios', [
            'nome'  => 'Bob',
            'email' => 'not-an-email',
        ]);
        $this->assertSame(422, $r->getStatusForTest());
    }

    public function test_destroy_nonexistent_returns_404(): void
    {
        $r = $this->dispatch('DELETE', '/api/v1/usuarios/999');
        $this->assertSame(404, $r->getStatusForTest());
    }
}
