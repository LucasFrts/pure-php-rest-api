<?php

namespace App\Entities;

enum StatusMatricula
{
    case Ativo;
    case Cancelado;

    public static function fromString(string $value): self
    {
        return match(strtolower($value)) {
            'ativo'     => self::Ativo,
            'cancelado' => self::Cancelado,
            default     => throw new \ValueError("Invalid status matricula: {$value}"),
        };
    }

    public function toString(): string
    {
        return match($this) {
            self::Ativo     => 'ativo',
            self::Cancelado => 'cancelado',
        };
    }
}
