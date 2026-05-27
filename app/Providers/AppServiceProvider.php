<?php

namespace App\Providers;

use App\Contracts\ConfigInterface;
use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\CursoServiceInterface;
use App\Contracts\Services\MatriculaServiceInterface;
use App\Contracts\Services\TurmaServiceInterface;
use App\Contracts\Services\UsuarioServiceInterface;
use App\Repositories\CursoRepository;
use App\Repositories\MatriculaRepository;
use App\Repositories\TurmaRepository;
use App\Repositories\UsuarioRepository;
use App\Services\CursoService;
use App\Services\MatriculaService;
use App\Services\TurmaService;
use App\Services\UsuarioService;
use App\Support\Config;
use App\Support\Container;
use App\Support\ExceptionHandler;
use App\Support\Logger;
use App\Support\Request;
use App\Support\Response;
use App\Support\ServiceProvider;
use PDO;
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

        $this->container->singleton(
            PDO::class,
            fn(Container $c) => new PDO(
                $c->get(ConfigInterface::class)->get('db')['dsn'],
                $c->get(ConfigInterface::class)->get('db')['user'],
                $c->get(ConfigInterface::class)->get('db')['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
        );

        $this->container->bind(
            CursoRepositoryInterface::class,
            fn(Container $c) => new CursoRepository($c->get(PDO::class))
        );

        $this->container->bind(
            TurmaRepositoryInterface::class,
            fn(Container $c) => new TurmaRepository($c->get(PDO::class))
        );

        $this->container->bind(
            UsuarioRepositoryInterface::class,
            fn(Container $c) => new UsuarioRepository($c->get(PDO::class))
        );

        $this->container->bind(
            MatriculaRepositoryInterface::class,
            fn(Container $c) => new MatriculaRepository($c->get(PDO::class))
        );

        $this->container->bind(
            CursoServiceInterface::class,
            fn(Container $c) => new CursoService($c->get(CursoRepositoryInterface::class))
        );

        $this->container->bind(
            TurmaServiceInterface::class,
            fn(Container $c) => new TurmaService(
                $c->get(TurmaRepositoryInterface::class),
                $c->get(CursoRepositoryInterface::class),
                $c->get(MatriculaRepositoryInterface::class)
            )
        );

        $this->container->bind(
            UsuarioServiceInterface::class,
            fn(Container $c) => new UsuarioService($c->get(UsuarioRepositoryInterface::class))
        );

        $this->container->bind(
            MatriculaServiceInterface::class,
            fn(Container $c) => new MatriculaService(
                $c->get(MatriculaRepositoryInterface::class),
                $c->get(TurmaRepositoryInterface::class)
            )
        );
    }
}
