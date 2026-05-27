<?php

namespace App\Contracts;

interface EntityInterface
{
    public function getId(): ?int;

    public function toArray(): array;
}
