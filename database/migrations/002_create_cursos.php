<?php

namespace Database\Migrations;

use Database\MigrationInterface;

class CreateCursos implements MigrationInterface
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS cursos (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                titulo     VARCHAR(255) NOT NULL,
                descricao  TEXT NOT NULL,
                tema       ENUM('inovacao','tecnologia','marketing','empreendedorismo','agro') NOT NULL,
                url_imagem VARCHAR(500) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
