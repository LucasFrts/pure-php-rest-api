<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/SeederInterface.php';

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

require_once __DIR__ . '/seeders/DatabaseSeeder.php';

$seeder = new \Database\Seeders\DatabaseSeeder();
$seeder->run($pdo);

echo "Seeding complete.\n";
