<?php

namespace Tests\Support;

use App\Enums\Temas;
use App\Exceptions\Http\UnprocessableEntity;
use App\Support\RequestValidator;
use PHPUnit\Framework\TestCase;

class RequestValidatorTest extends TestCase
{
    private RequestValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RequestValidator();
    }

    public function test_require_fields_throws_with_missing_list(): void
    {
        try {
            $this->validator->requireFields(['titulo' => 'PHP'], ['titulo', 'descricao', 'url_imagem']);
            $this->fail('Expected UnprocessableEntity');
        } catch (UnprocessableEntity $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertSame(['missing' => ['descricao', 'url_imagem']], $e->getErrors());
        }
    }

    public function test_parse_enum_throws_on_invalid_value(): void
    {
        $this->expectException(UnprocessableEntity::class);
        $this->validator->parseEnum('tema', 'invalido', Temas::fromString(...));
    }

    public function test_require_integers_throws_on_missing_field(): void
    {
        try {
            $this->validator->requireIntegers(['usuario_id' => 1], ['usuario_id', 'turma_id']);
            $this->fail('Expected UnprocessableEntity');
        } catch (UnprocessableEntity $e) {
            $this->assertArrayHasKey('invalid', $e->getErrors());
            $this->assertArrayHasKey('turma_id', $e->getErrors()['invalid']);
        }
    }
}
