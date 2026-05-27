<?php

namespace App\Support;

use App\Contracts\RequestValidatorInterface;
use App\Exceptions\Http\UnprocessableEntity;
use DateTime;

class RequestValidator implements RequestValidatorInterface
{
    /**
     * @param array<string, mixed> $data
     * @param list<string>         $fields
     */
    public function requireFields(array $data, array $fields): void
    {
        $missing = [];

        foreach ($fields as $field) {
            if (!$this->hasValue($data, $field)) {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw new UnprocessableEntity(
                'Campos obrigatórios ausentes ou inválidos',
                ['missing' => $missing]
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string>         $fields
     */
    public function requireIntegers(array $data, array $fields): void
    {
        $invalid = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $data) || !is_numeric($data[$field])) {
                $invalid[$field] = 'deve ser um número inteiro';
            }
        }

        if ($invalid !== []) {
            throw new UnprocessableEntity(
                'Campos obrigatórios ausentes ou inválidos',
                ['invalid' => $invalid]
            );
        }
    }

    /**
     * @template T
     * @param callable(string): T $parser
     * @return T
     */
    public function parseEnum(string $field, mixed $value, callable $parser): mixed
    {
        if (!is_string($value) || $value === '') {
            throw new UnprocessableEntity(
                "Campo {$field} inválido",
                ['invalid' => [$field => 'valor ausente ou inválido']]
            );
        }

        try {
            return $parser($value);
        } catch (\ValueError) {
            throw new UnprocessableEntity(
                "Campo {$field} inválido",
                ['invalid' => [$field => $value]]
            );
        }
    }

    public function parseDate(string $field, mixed $value): DateTime
    {
        if (!is_string($value) || $value === '') {
            throw new UnprocessableEntity(
                "Campo {$field} inválido",
                ['invalid' => [$field => 'valor ausente ou inválido']]
            );
        }

        try {
            return new DateTime($value);
        } catch (\Exception) {
            throw new UnprocessableEntity(
                "Campo {$field} inválido",
                ['invalid' => [$field => $value]]
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hasValue(array $data, string $field): bool
    {
        if (!array_key_exists($field, $data)) {
            return false;
        }

        $value = $data[$field];

        return $value !== null && $value !== '';
    }
}
