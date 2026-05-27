<?php

namespace Database\Migrations;

use Database\MigrationInterface;

class CreateMatriculas implements MigrationInterface
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS matriculas (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                turma_id   INT NOT NULL,
                curso_id   INT NOT NULL,
                status     ENUM('ativo','inativo','cancelado') NOT NULL DEFAULT 'ativo',
                UNIQUE KEY uq_matriculas_usuario_curso (usuario_id, curso_id),
                CONSTRAINT fk_matriculas_usuario FOREIGN KEY (usuario_id)
                    REFERENCES usuarios(id) ON DELETE CASCADE,
                CONSTRAINT fk_matriculas_turma FOREIGN KEY (turma_id)
                    REFERENCES turmas(id) ON DELETE CASCADE,
                CONSTRAINT fk_matriculas_curso FOREIGN KEY (curso_id)
                    REFERENCES cursos(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
