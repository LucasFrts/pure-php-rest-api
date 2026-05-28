<?php

namespace App\Enums;

use ValueError;

enum StatusTurma
{
    case Disponivel;
    case Encerrado;

    public static function fromString(string $value): self
    {
        return match(strtolower($value)) {
            'disponivel' => self::Disponivel,
            'encerrado'  => self::Encerrado,
            default      => throw new ValueError("Invalid status: {$value}"),
        };
    }

    public function toString(): string
    {
        return match($this) {
            self::Disponivel => 'disponivel',
            self::Encerrado  => 'encerrado',
        };
    }
}