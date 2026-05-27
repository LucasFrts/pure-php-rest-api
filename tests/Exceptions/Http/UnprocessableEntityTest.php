<?php

namespace Tests\Exceptions\Http;

use App\Exceptions\Http\NotFound;
use App\Exceptions\Http\UnprocessableEntity;
use PHPUnit\Framework\TestCase;

class UnprocessableEntityTest extends TestCase
{
    public function test_not_found_status_code(): void
    {
        $e = new NotFound('Recurso não encontrado');
        $this->assertSame(404, $e->getStatusCode());
        $this->assertSame('Recurso não encontrado', $e->getMessage());
    }

    public function test_unprocessable_entity_status_code(): void
    {
        $e = new UnprocessableEntity('Dados inválidos');
        $this->assertSame(422, $e->getStatusCode());
        $this->assertSame('Dados inválidos', $e->getMessage());
    }
}
