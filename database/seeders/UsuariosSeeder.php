<?php

namespace Database\Seeders;

use Database\SeederInterface;

class UsuariosSeeder implements SeederInterface
{
    public function run(\PDO $pdo): void
    {
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO usuarios (nome, email) VALUES (?, ?)"
        );

        $stmt->execute(['Ana Lima',    'ana.lima@email.com']);
        $stmt->execute(['Bruno Costa', 'bruno.costa@email.com']);
        $stmt->execute(['Carla Souza', 'carla.souza@email.com']);

        echo "[seeded] usuarios\n";
    }
}
