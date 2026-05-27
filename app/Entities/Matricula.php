<?php

namespace App\Entities;

class Matricula
{
    public function __construct(
        private int $usuarioId,
        private int $turmaId,
        private int $cursoId,
        private ?int $id = null
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuarioId(): int
    {
        return $this->usuarioId;
    }

    public function getTurmaId(): int
    {
        return $this->turmaId;
    }

    public function getCursoId(): int
    {
        return $this->cursoId;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
