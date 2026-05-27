<?php

namespace Database\Seeders;

use Database\SeederInterface;

class DatabaseSeeder implements SeederInterface
{
    public function run(\PDO $pdo): void
    {
        require_once __DIR__ . '/UsuariosSeeder.php';
        require_once __DIR__ . '/CursosSeeder.php';
        require_once __DIR__ . '/TurmasSeeder.php';
        require_once __DIR__ . '/MatriculasSeeder.php';

        (new UsuariosSeeder())->run($pdo);
        (new CursosSeeder())->run($pdo);
        (new TurmasSeeder())->run($pdo);
        (new MatriculasSeeder())->run($pdo);
    }
}
