<?php

namespace App\Entities;

use App\ValueObjects\Email;

class Usuario
{
    public function __construct(
        private string $nome,
        private Email $email,
        private ?int $id = null
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getEmail(): Email { return $this->email; }
    public function setId(int $id): void { $this->id = $id; }
}