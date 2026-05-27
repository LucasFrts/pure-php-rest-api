<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/MigrationInterface.php';

$config = require __DIR__ . '/../config.php';

try {
    $pdo = new \PDO(
        $config['db']['dsn'],
        $config['db']['user'],
        $config['db']['pass'],
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e) {
    fwrite(STDERR, "Connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        ran_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$result = $pdo->query("SELECT migration FROM migrations");
if ($result === false) {
    fwrite(STDERR, "Failed to query migrations table.\n");
    exit(1);
}
$ran = $result->fetchAll(\PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/migrations/*.php');
if ($files === false) {
    fwrite(STDERR, "Migrations directory not found.\n");
    exit(1);
}
sort($files);

$classMap = [
    '001_create_usuarios'   => \Database\Migrations\CreateUsuarios::class,
    '002_create_cursos'     => \Database\Migrations\CreateCursos::class,
    '003_create_turmas'     => \Database\Migrations\CreateTurmas::class,
    '004_create_matriculas' => \Database\Migrations\CreateMatriculas::class,
];

foreach ($files as $file) {
    $name = basename($file, '.php');

    if (in_array($name, $ran, true)) {
        echo "[skip] {$name}\n";
        continue;
    }

    if (!isset($classMap[$name])) {
        fwrite(STDERR, "[warn] no class mapping for {$name}, skipping\n");
        continue;
    }

    require_once $file;

    $fqcn = $classMap[$name];

    try {
        $migration = new $fqcn();

        // MySQL DDL causes implicit commit, so transactions won't protect DDL migrations.
        // This does protect pure DML migrations and makes intent explicit.
        $pdo->beginTransaction();
        $migration->up($pdo);
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        fwrite(STDERR, "[error] {$name}: " . $e->getMessage() . "\n");
        exit(1);
    }

    $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
    $stmt->execute([$name]);

    echo "[done] {$name}\n";
}

echo "Migrations complete.\n";
