<?php

namespace App\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Support\Container;

class BaseController
{
    public function response(): ResponseInterface
    {
        return $this->getService(ResponseInterface::class);
    }

    public function request(): RequestInterface
    {
        return $this->getService(RequestInterface::class);
    }

    private function getService(string $serviceInterface) : mixed
    {
        return Container::getContainer()->get($serviceInterface);
    }
}
