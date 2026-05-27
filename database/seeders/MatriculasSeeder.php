<?php

namespace Database\Seeders;

use Database\SeederInterface;

class MatriculasSeeder implements SeederInterface
{
    public function run(\PDO $pdo): void
    {
        $usuarios = $pdo->query("SELECT id FROM usuarios ORDER BY id")->fetchAll(\PDO::FETCH_COLUMN);
        $turmas   = $pdo->query("SELECT id, curso_id FROM turmas WHERE status = 'disponivel' ORDER BY id")->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($usuarios) || empty($turmas)) {
            echo "[skip] matriculas — missing usuarios or turmas\n";
            return;
        }

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO matriculas (usuario_id, turma_id, curso_id, status)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($usuarios as $index => $usuarioId) {
            $turma = $turmas[$index % count($turmas)];
            $stmt->execute([
                $usuarioId,
                $turma['id'],
                $turma['curso_id'],
                'ativo',
            ]);
        }

        echo "[seeded] matriculas\n";
    }
}
