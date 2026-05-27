<?php

namespace Database\Migrations;

use Database\MigrationInterface;

class CreateUsuarios implements MigrationInterface
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id    INT AUTO_INCREMENT PRIMARY KEY,
                nome  VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                UNIQUE KEY uq_usuarios_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
