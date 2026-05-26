<?php

namespace App\Support;

class Config
{
    private static array $config = [];

    public static function get(string $key, $default = null)
    {
        if (empty(self::$config)) {
            self::$config = require_once(__DIR__.'/../../config.php');
        }

        return !empty(self::$config[$key])?self::$config[$key]:$default;
    }
}