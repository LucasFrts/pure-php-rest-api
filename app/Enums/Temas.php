<?php

namespace App\Enums;

enum Temas
{
    case Inovacao;
    case Tecnologia;
    case Marketing;
    case Empreendedorismo;
    case Agro;

    public static function fromString(string $value): self
    {
        return match(strtolower($value)) {
            'inovacao'         => self::Inovacao,
            'tecnologia'       => self::Tecnologia,
            'marketing'        => self::Marketing,
            'empreendedorismo' => self::Empreendedorismo,
            'agro'             => self::Agro,
            default            => throw new \ValueError("Invalid tema: {$value}"),
        };
    }

    public function toString(): string
    {
        return match($this) {
            self::Inovacao         => 'inovacao',
            self::Tecnologia       => 'tecnologia',
            self::Marketing        => 'marketing',
            self::Empreendedorismo => 'empreendedorismo',
            self::Agro             => 'agro',
        };
    }
}