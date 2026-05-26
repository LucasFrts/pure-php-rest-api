# dot-group API

Pure PHP REST API framework built from scratch. No third-party frameworks — only PSR standards and Monolog.

## Requirements

- PHP 8.3+
- Composer

## Installation

```bash
composer install
```

## Running Tests

```bash
./vendor/bin/phpunit
```

## Project Structure

```
app/
  Contracts/
    ConfigInterface.php         # Contract for config access
    HttpExceptionInterface.php  # Contract for HTTP-aware exceptions
  Exceptions/
    Http/
      NotFound.php              # 404 HTTP exception
  Http/
    Controllers/                # Application controllers go here
  Providers/
    AppServiceProvider.php      # Registers Config, Logger, ExceptionHandler
  Support/
    Config.php                  # Injectable configuration
    Container.php               # PSR-11 DI container with autowiring
    Dispatcher.php              # Resolves and invokes controllers
    ExceptionHandler.php        # Converts exceptions to RFC 7807 responses
    Logger.php                  # PSR-3 wrapper over Monolog
    Request.php                 # (placeholder)
    Response.php                # (placeholder)
    Router.php                  # HTTP router
    ServiceProvider.php         # Abstract base for service providers
bootstrap/
  app.php                       # Builds container, runs providers
config.php                      # Application configuration
routes/
  api.php                       # Route definitions
index.php                       # Entry point
```

## Architecture

### Request Lifecycle

```
HTTP Request
  → index.php
  → bootstrap/app.php     builds container, runs service providers
  → routes/api.php        registers routes on Router
  → Router::route()       matches URI + method → route array
  → Dispatcher::dispatch() resolves App\Http\Controllers\{Name} from container
  → $controller->$action()
  → (exception) → ExceptionHandler::handle() → RFC 7807 JSON response
```

### Dependency Injection Container (PSR-11)

`App\Support\Container` — reflection-based autowiring. Constructor dependencies typed with class/interface hints are resolved automatically.

```php
// Factory — new instance each call
$container->bind(SomeInterface::class, fn(Container $c) => new SomeImpl());

// Singleton — same instance every call
$container->singleton(SomeInterface::class, fn() => new SomeImpl());

// Auto-resolve — no registration needed for concrete classes
$container->get(MyController::class); // resolved via reflection
```

### Service Providers

Providers register bindings into the container. Add a new provider to the `$providers` array in `bootstrap/app.php`.

```php
// app/Providers/MyServiceProvider.php
class MyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(MyInterface::class, fn() => new MyImpl());
    }
}
```

### Router

Fluent interface. Routes follow `'ControllerName@methodName'` syntax.

```php
$router
    ->get('/users', 'User@index')
    ->post('/users', 'User@store')
    ->get('/users/{id}', 'User@show')  // note: path params not yet implemented
    ->put('/users/{id}', 'User@update')
    ->delete('/users/{id}', 'User@destroy');
```

Supported methods: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`.

### Controllers

Placed in `app/Http/Controllers/`. Class name must match the first segment of the route string. Dependencies are injected automatically via the container.

```php
// app/Http/Controllers/User.php
namespace App\Http\Controllers;

use App\Contracts\ConfigInterface;

class User
{
    public function __construct(private ConfigInterface $config) {}

    public function index(): void
    {
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['users' => []]);
    }
}
```

### Configuration

`config.php` returns an associative array. Access via injected `ConfigInterface`:

```php
// config.php
return [
    'LOG_PATH' => __DIR__ . '/storage/logs',
    'APP_ENV'  => 'local',
];

// In a class:
public function __construct(private ConfigInterface $config) {}

$env = $this->config->get('APP_ENV', 'production');
```

### Exception Handling

Exceptions thrown anywhere in the request lifecycle are caught in `index.php` and delegated to `ExceptionHandler`.

**HTTP exceptions** — implement `HttpExceptionInterface`:

```php
// app/Exceptions/Http/Unauthorized.php
namespace App\Exceptions\Http;

use App\Contracts\HttpExceptionInterface;
use Exception;
use Throwable;

class Unauthorized extends Exception implements HttpExceptionInterface
{
    public function __construct(string $message = 'Unauthorized', Throwable|null $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }
}
```

Throwing this anywhere produces:

```json
HTTP/1.1 401 Unauthorized
Content-Type: application/problem+json

{
    "status": 401,
    "title": "Unauthorized",
    "detail": "Unauthorized"
}
```

**Generic exceptions** produce a safe 500:

```json
HTTP/1.1 500 Internal Server Error
Content-Type: application/problem+json

{
    "status": 500,
    "title": "Internal Server Error",
    "detail": "Internal Server Error"
}
```

Internal error messages are never exposed.

### Logging

`Psr\Log\LoggerInterface` is registered in the container. Inject it where needed:

```php
use Psr\Log\LoggerInterface;

class MyService
{
    public function __construct(private LoggerInterface $logger) {}

    public function doSomething(): void
    {
        $this->logger->info('doing something', ['key' => 'value']);
    }
}
```

Logs are written to `storage/logs/app.log`. Configure `LOG_PATH` in `config.php` to change the directory.

## PSR Compliance

| Standard | Applied to |
|----------|-----------|
| PSR-4    | Autoload — `App\` → `app/`, `Tests\` → `tests/` |
| PSR-11   | `Container` implements `ContainerInterface` |
| PSR-3    | `Logger` implements `LoggerInterface` via `AbstractLogger` |
| RFC 7807 | Error responses use Problem Details format |
