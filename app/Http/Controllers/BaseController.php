<?php

namespace App\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\RequestValidatorInterface;
use App\Contracts\ResponseInterface;
use App\Support\Container;
use Psr\Log\LoggerInterface;

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

    public function validator(): RequestValidatorInterface
    {
        return $this->getService(RequestValidatorInterface::class);
    }

    public function logger() : LoggerInterface
    {
        return $this->getService(LoggerInterface::class);
    }

    private function getService(string $serviceInterface) : mixed
    {
        return Container::getContainer()->get($serviceInterface);
    }
}
