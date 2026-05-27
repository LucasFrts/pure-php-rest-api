<?php

namespace App\Repositories;

abstract class BaseRepository
{
    public function __construct(protected \PDO $pdo)
    {
    }
}
