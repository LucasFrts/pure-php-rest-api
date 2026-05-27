<?php

namespace App\Entities;

use App\Contracts\EntityInterface;

class Curso implements EntityInterface
{
    public function __construct(
        private string $titulo,
        private string $descrição,
        private Temas $tema,
        private string $urlImagem,
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

    public function getTema(): Temas
    {
        return $this->tema;
    }

    public function getUrlImagem(): string
    {
        return $this->urlImagem;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'titulo'     => $this->titulo,
            'descricao'  => $this->descrição,
            'tema'       => $this->tema->toString(),
            'url_imagem' => $this->urlImagem,
        ];
    }
}
