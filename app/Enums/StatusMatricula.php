<?php

namespace App\Enums;

enum StatusMatricula
{
    case Ativo;
    case Inativo;
    case Cancelado;

    public static function fromString(string $value): self
    {
        return match(strtolower($value)) {
            'ativo'     => self::Ativo,
            'inativo'   => self::Inativo,
            'cancelado' => self::Cancelado,
            default     => throw new \ValueError("Invalid status matricula: {$value}"),
        };
    }

    public function toString(): string
    {
        return match($this) {
            self::Ativo     => 'ativo',
            self::Inativo   => 'inativo',
            self::Cancelado => 'cancelado',
        };
    }
}
