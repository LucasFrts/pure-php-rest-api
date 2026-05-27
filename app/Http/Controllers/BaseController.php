<?php

namespace App\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Support\Container;

class BaseController
{
    public function response(): ResponseInterface
    {
        return Container::getContainer()->get(ResponseInterface::class);
    }

    public function request(): RequestInterface
    {
        return Container::getContainer()->get(RequestInterface::class);
    }
}
