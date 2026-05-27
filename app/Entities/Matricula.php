<?php

namespace App\Entities;

use App\Contracts\EntityInterface;

class Matricula implements EntityInterface
{
    public function __construct(
        private int $usuarioId,
        private int $turmaId,
        private int $cursoId,
        private StatusMatricula $status = StatusMatricula::Ativo,
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

    public function getStatus(): StatusMatricula
    {
        return $this->status;
    }

    public function setStatus(StatusMatricula $status): void
    {
        $this->status = $status;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'usuario_id' => $this->usuarioId,
            'turma_id'   => $this->turmaId,
            'curso_id'   => $this->cursoId,
            'status'     => $this->status->toString(),
        ];
    }
}
