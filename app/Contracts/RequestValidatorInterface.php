<?php

namespace App\Contracts;

use DateTime;

/**
 * 
 * @see App\Support\RequestValidator
 * 
 */
interface RequestValidatorInterface
{
    /**
     * @param array<string, mixed> $data
     * @param list<string>         $fields
     */
    public function requireFields(array $data, array $fields): void;

    /**
     * @param array<string, mixed> $data
     * @param list<string>         $fields
     */
    public function requireIntegers(array $data, array $fields): void;

    /**
     * @template T
     * @param callable(string): T $parser
     * @return T
     */
    public function parseEnum(string $field, mixed $value, callable $parser): mixed;

    public function parseDate(string $field, mixed $value): DateTime;
}
