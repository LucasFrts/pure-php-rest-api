<?php

require __DIR__ . '/vendor/autoload.php';

use App\Support\Config;
$LOG_PATH = Config::get('LOG_PATH', '');

echo "[LOG_PATH]: $LOG_PATH";

$router = require __DIR__ . '/routes/api.php';
var_dump($router);

$method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];
$request_uri = parse_url($_SERVER['REQUEST_URI']);
$path = $request_uri['path'];
$query = $request_uri['query'];

$router->route($path, $method, $query);