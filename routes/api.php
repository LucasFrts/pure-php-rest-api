<?php

use App\Support\Router;

$router = new Router;
$router
    ->prefix('/api/v1')
    ->get('/turmas', 'TurmasController@index');

return $router;