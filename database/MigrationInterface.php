<?php

namespace Database;

interface MigrationInterface
{
    public function up(\PDO $pdo): void;
}
