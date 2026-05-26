<?php

use App\Support\Router;

$router = new Router;
$router
    ->get('/', 'Home@Dale')
    ->get('/teste', 'Aloha@makaka');

return $router;