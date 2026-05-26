<?php

require __DIR__ . '/vendor/autoload.php';

$container = require __DIR__ . '/bootstrap/app.php';
$router    = require __DIR__ . '/routes/api.php';

$method  = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path    = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';
$handler = $container->get(\App\Support\ExceptionHandler::class);

try {
    $route = $router->route($path, $method);
    $dispatcher = new \App\Support\Dispatcher($container);
    $dispatcher->dispatch($route);
} catch (\Throwable $e) {
    $handler->handle($e);
}