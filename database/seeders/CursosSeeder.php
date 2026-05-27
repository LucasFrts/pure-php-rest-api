<?php

namespace Database\Seeders;

use Database\SeederInterface;

class CursosSeeder implements SeederInterface
{
    public function run(\PDO $pdo): void
    {
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO cursos (titulo, descricao, tema, url_imagem) VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            'Fundamentos de Inovação',
            'Aprenda os principais conceitos e ferramentas de inovação aplicados ao mercado.',
            'inovacao',
            'https://example.com/images/inovacao.jpg',
        ]);

        $stmt->execute([
            'Desenvolvimento Web com PHP',
            'Curso completo de desenvolvimento backend com PHP moderno.',
            'tecnologia',
            'https://example.com/images/php.jpg',
        ]);

        $stmt->execute([
            'Marketing Digital na Prática',
            'Estratégias de marketing digital para pequenas e médias empresas.',
            'marketing',
            'https://example.com/images/marketing.jpg',
        ]);

        echo "[seeded] cursos\n";
    }
}
