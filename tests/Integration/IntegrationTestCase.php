<?php

namespace Tests\Integration;

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
use App\Exceptions\Http\NotFound;
use App\Exceptions\Http\RouteNotFound;
use App\Exceptions\Http\UnprocessableEntity;
use App\Repositories\CursoRepository;
use App\Repositories\MatriculaRepository;
use App\Repositories\TurmaRepository;
use App\Repositories\UsuarioRepository;
use App\Services\CursoService;
use App\Services\MatriculaService;
use App\Services\TurmaService;
use App\Services\UsuarioService;
use App\Support\Container;
use App\Support\Dispatcher;
use App\Support\Request;
use App\Support\Response;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class IntegrationTestCase extends TestCase
{
    protected Container $container;
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('PRAGMA foreign_keys = ON;');
        $this->pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
        $this->bootContainer();
    }

    private function bootContainer(): void
    {
        $pdo = $this->pdo;
        $container = new Container();

        $container->singleton(PDO::class, fn() => $pdo);
        $container->singleton(LoggerInterface::class, fn() => new NullLogger());
        $container->singleton(ResponseInterface::class, fn() => new Response());
        $container->singleton(RequestInterface::class, fn() => new Request());

        $container->bind(
            CursoRepositoryInterface::class,
            fn(Container $c) => new CursoRepository($c->get(PDO::class))
        );
        $container->bind(
            TurmaRepositoryInterface::class,
            fn(Container $c) => new TurmaRepository($c->get(PDO::class))
        );
        $container->bind(
            UsuarioRepositoryInterface::class,
            fn(Container $c) => new UsuarioRepository($c->get(PDO::class))
        );
        $container->bind(
            MatriculaRepositoryInterface::class,
            fn(Container $c) => new MatriculaRepository($c->get(PDO::class))
        );
        $container->bind(
            CursoServiceInterface::class,
            fn(Container $c) => new CursoService(
                $c->get(CursoRepositoryInterface::class),
                $c->get(LoggerInterface::class)
            )
        );
        $container->bind(
            TurmaServiceInterface::class,
            fn(Container $c) => new TurmaService(
                $c->get(TurmaRepositoryInterface::class),
                $c->get(CursoRepositoryInterface::class),
                $c->get(MatriculaRepositoryInterface::class),
                $c->get(LoggerInterface::class)
            )
        );
        $container->bind(
            UsuarioServiceInterface::class,
            fn(Container $c) => new UsuarioService(
                $c->get(UsuarioRepositoryInterface::class),
                $c->get(LoggerInterface::class)
            )
        );
        $container->bind(
            MatriculaServiceInterface::class,
            fn(Container $c) => new MatriculaService(
                $c->get(MatriculaRepositoryInterface::class),
                $c->get(TurmaRepositoryInterface::class),
                $c->get(LoggerInterface::class)
            )
        );

        Container::setContainer($container);
        $this->container = $container;
    }

    /**
     * Simulates an HTTP request through Router → Dispatcher → Controller → Service → Repository.
     * HTTP exceptions (404, 422) are caught and converted to the appropriate Response.
     */
    protected function dispatch(
        string $method,
        string $uri,
        array $body = [],
        array $query = []
    ): ResponseInterface {
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI']    = $uri;
        $_SERVER['CONTENT_TYPE']   = 'application/x-www-form-urlencoded';
        $_GET  = $query;
        $_POST = $body;

        // Re-instantiate Request so it picks up the new superglobals.
        $this->container->forgetInstance(RequestInterface::class);
        $this->container->singleton(RequestInterface::class, fn() => new Request());

        try {
            $router = require __DIR__ . '/../../routes/api.php';
            $route  = $router->route($uri, $method);

            $dispatcher = new Dispatcher($this->container);
            return $dispatcher->dispatch($route) ?? $this->container->get(ResponseInterface::class);
        } catch (RouteNotFound $e) {
            return (new Response())->notFound(['error' => 'Not found']);
        } catch (NotFound $e) {
            return (new Response())->notFound(['error' => $e->getMessage()]);
        } catch (UnprocessableEntity $e) {
            return (new Response())->unprocessable(['error' => $e->getMessage()]);
        }
    }
}
