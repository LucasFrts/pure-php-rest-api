<?php

namespace App\Entities;

use App\Contracts\EntityInterface;
use App\Enums\StatusTurma;
use App\Exceptions\Http\UnprocessableEntity;
use DateTime;

class Turma implements EntityInterface
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    public function getDescricao(): string
    {
        return $this->descrição;
    }

    public function getQuantidadeVagas(): int
    {
        return $this->quantidadeVagas;
    }

    public function getStatus(): StatusTurma
    {
        return $this->status;
    }

    public function getDataInicio(): DateTime
    {
        return $this->dataInicio;
    }

    public function getDataFim(): DateTime
    {
        return $this->dataFim;
    }

    public function getCursoId(): int
    {
        return $this->cursoId;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function decrementVaga(): void
    {
        $this->quantidadeVagas--;
    }

    public function validated() : self
    {
        if ($this->dataInicio > $this->dataFim) {
            throw new UnprocessableEntity("A data final não pode ser anterior que a data de ínicio.");
        }

        $now = new DateTime();
        if ($now > $this->dataInicio || $now > $this->dataFim) {
            throw new UnprocessableEntity("As datas de início e fim não podem ser anteriores à data de hoje");
        }
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'curso_id'         => $this->cursoId,
            'titulo'           => $this->titulo,
            'descricao'        => $this->descrição,
            'quantidade_vagas' => $this->quantidadeVagas,
            'status'           => $this->status->toString(),
            'data_inicio'      => $this->dataInicio->format('Y-m-d'),
            'data_fim'         => $this->dataFim->format('Y-m-d'),
        ];
    }
}
