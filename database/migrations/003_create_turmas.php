<?php

namespace Database\Migrations;

use Database\MigrationInterface;

class CreateTurmas implements MigrationInterface
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS turmas (
                id               INT AUTO_INCREMENT PRIMARY KEY,
                curso_id         INT NOT NULL,
                titulo           VARCHAR(255) NOT NULL,
                descricao        TEXT NOT NULL,
                quantidade_vagas INT NOT NULL,
                status           ENUM('disponivel','encerrado') NOT NULL DEFAULT 'disponivel',
                data_inicio      DATE NOT NULL,
                data_fim         DATE NOT NULL,
                CONSTRAINT fk_turmas_curso FOREIGN KEY (curso_id)
                    REFERENCES cursos(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
