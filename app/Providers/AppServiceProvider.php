<?php

namespace App\Providers;

use App\Contracts\ConfigInterface;
use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Support\Config;
use App\Support\Container;
use App\Support\ExceptionHandler;
use App\Support\Logger;
use App\Support\Request;
use App\Support\Response;
use App\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            ConfigInterface::class,
            fn() => new Config(require __DIR__ . '/../../config.php')
        );

        $this->container->singleton(
            LoggerInterface::class,
            fn(Container $c) => new Logger($c->get(ConfigInterface::class))
        );

        $this->container->singleton(
            ExceptionHandler::class,
            fn(Container $c) => new ExceptionHandler($c->get(ConfigInterface::class))
        );

        $this->container->singleton(
            ResponseInterface::class,
            fn() => new Response()
        );

        $this->container->singleton(
            Response::class,
            fn(Container $c) => $c->get(ResponseInterface::class)
        );

        $this->container->singleton(
            RequestInterface::class,
            fn() => new Request()
        );

        $this->container->singleton(
            Request::class,
            fn(Container $c) => $c->get(RequestInterface::class)
        );
    }
}
