<?php

namespace Database;

interface SeederInterface
{
    public function run(\PDO $pdo): void;
}
