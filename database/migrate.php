<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';

$pdo = new \PDO(
    $config['db']['dsn'],
    $config['db']['user'],
    $config['db']['pass'],
    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        ran_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$ran = $pdo->query("SELECT migration FROM migrations")->fetchAll(\PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/migrations/*.php');
sort($files);

foreach ($files as $file) {
    $name = basename($file, '.php');

    if (in_array($name, $ran, true)) {
        echo "[skip] {$name}\n";
        continue;
    }

    require_once $file;

    $class = basename($file, '.php');
    $parts = explode('_', $class, 2);
    $className = implode('_', array_map('ucfirst', explode('_', $parts[1] ?? $parts[0])));
    $fqcn = 'Database\\Migrations\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $parts[1] ?? $class)));

    $migration = new $fqcn();
    $migration->up($pdo);

    $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
    $stmt->execute([$name]);

    echo "[done] {$name}\n";
}

echo "Migrations complete.\n";
