<?php

namespace App\Http\Controllers;

use App\Contracts\ResponseInterface;
use App\Support\Container;

class BaseController
{
    public function response() : ResponseInterface
    {
        $container = Container::getContainer();
        $responseService = $container->get(ResponseInterface::class);
        return $responseService;
    }
}
