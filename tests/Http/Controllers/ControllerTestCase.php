<?php

namespace Tests\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\RequestValidatorInterface;
use App\Contracts\ResponseInterface;
use App\Support\Container;
use App\Support\Request;
use App\Support\RequestValidator;
use App\Support\Response;
use PHPUnit\Framework\TestCase;

abstract class ControllerTestCase extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->singleton(ResponseInterface::class, fn() => new Response());
        $this->container->singleton(RequestInterface::class, fn() => new Request());
        $this->container->singleton(RequestValidatorInterface::class, fn() => new RequestValidator());
        Container::setContainer($this->container);
    }

    protected function withPostBody(array $body): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE']   = 'application/x-www-form-urlencoded';
        $_POST = $body;

        $this->container->forgetInstance(RequestInterface::class);
        $this->container->singleton(RequestInterface::class, fn() => new Request());
    }
}
