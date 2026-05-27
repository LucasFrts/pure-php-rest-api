<?php

namespace Database\Seeders;

use Database\SeederInterface;

class TurmasSeeder implements SeederInterface
{
    public function run(\PDO $pdo): void
    {
        $cursos = $pdo->query("SELECT id FROM cursos ORDER BY id")->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($cursos)) {
            echo "[skip] turmas — no cursos found\n";
            return;
        }

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO turmas
                (curso_id, titulo, descricao, quantidade_vagas, status, data_inicio, data_fim)
            VALUES
                (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($cursos as $cursoId) {
            $stmt->execute([
                $cursoId,
                'Turma A',
                'Primeira turma do curso.',
                30,
                'disponivel',
                date('Y-m-d'),
                date('Y-m-d', strtotime('+3 months')),
            ]);

            $stmt->execute([
                $cursoId,
                'Turma B',
                'Segunda turma do curso.',
                20,
                'encerrado',
                date('Y-m-d', strtotime('-6 months')),
                date('Y-m-d', strtotime('-3 months')),
            ]);
        }

        echo "[seeded] turmas\n";
    }
}
