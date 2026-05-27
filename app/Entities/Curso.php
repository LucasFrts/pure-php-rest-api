<?php

namespace App\Entities;

class Curso
{
    public function __construct(
        private string $titulo,
        private string $descrição,
        private Temas $tema,
        private string $urlImagem,
        private ?int $id = null
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTitulo(): string { return $this->titulo; }
    public function getDescricao(): string { return $this->descrição; }
    public function getTema(): Temas { return $this->tema; }
    public function getUrlImagem(): string { return $this->urlImagem; }
    public function setId(int $id): void { $this->id = $id; }
}