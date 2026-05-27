<?php

namespace Tests\Entities;

use App\Entities\Temas;
use PHPUnit\Framework\TestCase;

class TemasTest extends TestCase
{
    public function test_from_string_all_cases(): void
    {
        $this->assertSame(Temas::Inovacao, Temas::fromString('inovacao'));
        $this->assertSame(Temas::Tecnologia, Temas::fromString('tecnologia'));
        $this->assertSame(Temas::Marketing, Temas::fromString('marketing'));
        $this->assertSame(Temas::Empreendedorismo, Temas::fromString('empreendedorismo'));
        $this->assertSame(Temas::Agro, Temas::fromString('agro'));
    }

    public function test_from_string_invalid_throws(): void
    {
        $this->expectException(\ValueError::class);
        Temas::fromString('outro');
    }

    public function test_to_string_roundtrip(): void
    {
        foreach (Temas::cases() as $tema) {
            $this->assertSame($tema, Temas::fromString($tema->toString()));
        }
    }
}
