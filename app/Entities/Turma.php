<?php

namespace App\Entities;

use DateTime;

class Turma
{
    public function __construct(
        private string $titulo,
        private string $descrição,
        private int $quantidadeVagas,
        private StatusTurma $status,
        private DateTime $dataInicio,
        private DateTime $dataFim,
        private int $cursoId,
        private ?int $id = null
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTitulo(): string { return $this->titulo; }
    public function getDescricao(): string { return $this->descrição; }
    public function getQuantidadeVagas(): int { return $this->quantidadeVagas; }
    public function getStatus(): StatusTurma { return $this->status; }
    public function getDataInicio(): DateTime { return $this->dataInicio; }
    public function getDataFim(): DateTime { return $this->dataFim; }
    public function getCursoId(): int { return $this->cursoId; }
    public function setId(int $id): void { $this->id = $id; }
}