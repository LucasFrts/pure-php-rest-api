<?php

use App\Support\Container;

$container = Container::getContainer();

$providers = [
    \App\Providers\AppServiceProvider::class,
];

foreach ($providers as $provider) {
    (new $provider($container))->register();
}

return $container;
