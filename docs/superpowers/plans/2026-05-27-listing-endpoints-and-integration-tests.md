# Listing Endpoints + Integration Tests — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add GET /usuarios and GET /turmas listing endpoints, fix Router dynamic param matching and Dispatcher param forwarding, and create a full integration test suite using SQLite in-memory.

**Architecture:** Router converts `{param}` segments to named-capture-group regex and returns extracted params alongside the route; Dispatcher resolves controllers by FQCN directly (fixing existing double-namespace bug) and forwards params as positional args, returning `?ResponseInterface` instead of calling `send()` internally; `IntegrationTestCase` boots a real Container with SQLite in-memory PDO and wraps dispatch with HTTP exception handling for end-to-end tests.

**Tech Stack:** PHP 8.x, PHPUnit 11, PDO SQLite in-memory.

> **⚠️ Do NOT create an additional commit after all tasks complete. Each task has its own commit.**

---

## File Map

| File | Action | Responsibility |
|------|--------|----------------|
| `app/Support/Router.php` | Modify | Regex matching for `{param}` segments, return `params` in route array |
| `app/Support/Dispatcher.php` | Modify | Fix FQCN double-namespace bug, forward route params as positional args, return `?ResponseInterface` |
| `index.php` | Modify | Call `->send()` on response returned by Dispatcher |
| `routes/api.php` | Modify | Add `return $router;` + 3 new listing routes |
| `app/Repositories/TurmaRepository.php` | Modify | Add `curso_id` filter to `get()` |
| `app/Http/Controllers/UsuariosController.php` | Modify | Add `index()` method |
| `app/Http/Controllers/TurmasController.php` | Modify | Add `index()` and `indexByCurso(int $cursoId)` methods |
| `tests/Support/RouterTest.php` | Modify | Add tests for dynamic param matching |
| `tests/Http/Controllers/UsuariosControllerTest.php` | Modify | Add `test_index_returns_200_with_list()` |
| `tests/Http/Controllers/TurmasControllerTest.php` | Modify | Add `test_index_returns_200_with_list()` and `test_index_by_curso_returns_200_with_list()` |
| `phpunit.xml` | Modify | Add Integration testsuite directory |
| `tests/Integration/schema.sql` | Create | SQLite-compatible DDL for all 4 tables |
| `tests/Integration/IntegrationTestCase.php` | Create | Base class: SQLite setup, container bootstrap, `dispatch()` helper |
| `tests/Integration/CursosIntegrationTest.php` | Create | CRUD + error cases for Cursos |
| `tests/Integration/UsuariosIntegrationTest.php` | Create | CRUD + error cases for Usuarios |
| `tests/Integration/TurmasIntegrationTest.php` | Create | CRUD + listing by curso + error cases |
| `tests/Integration/MatriculasIntegrationTest.php` | Create | Create, list by usuario, delete |
| `tests/Integration/BusinessFlowIntegrationTest.php` | Create | Full flow: curso → turma → usuario → matrícula → encerrar turma → verificar inativação |

---

## Task 1: Router — Dynamic Param Matching

**Files:**
- Modify: `app/Support/Router.php`
- Modify: `tests/Support/RouterTest.php`

- [ ] **Step 1: Add failing tests to RouterTest**

Open `tests/Support/RouterTest.php` and add these methods to the existing test class (do not remove existing tests):

```php
public function test_route_extracts_single_dynamic_param(): void
{
    $router = new Router();
    $router->get('/items/{id}', [\stdClass::class, 'show']);
    $route = $router->route('/items/42', 'GET');
    $this->assertSame(['id' => '42'], $route['params']);
}

public function test_route_extracts_multiple_dynamic_params(): void
{
    $router = new Router();
    $router->get('/a/{foo}/b/{bar}', [\stdClass::class, 'show']);
    $route = $router->route('/a/1/b/2', 'GET');
    $this->assertSame(['foo' => '1', 'bar' => '2'], $route['params']);
}

public function test_route_static_returns_empty_params(): void
{
    $router = new Router();
    $router->get('/items', [\stdClass::class, 'index']);
    $route = $router->route('/items', 'GET');
    $this->assertSame([], $route['params']);
}

public function test_route_does_not_match_wrong_segment_count(): void
{
    $router = new Router();
    $router->get('/items/{id}', [\stdClass::class, 'show']);
    $this->expectException(\App\Exceptions\Http\RouteNotFound::class);
    $router->route('/items/42/extra', 'GET');
}
```

- [ ] **Step 2: Run new tests — verify they FAIL**

```bash
./vendor/bin/phpunit tests/Support/RouterTest.php --colors
```

Expected: FAIL — `route()` still does exact match, `params` key doesn't exist.

- [ ] **Step 3: Update `route()` and add `toRegex()` in Router.php**

Replace the `route()` method and add the `toRegex()` private method. Keep all other methods unchanged:

```php
public function route(string $uri, string $method): array
{
    foreach ($this->routes as $route) {
        $pattern = $this->toRegex($route['uri']);
        if (preg_match($pattern, $uri, $matches) && $route['method'] === strtoupper($method)) {
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return array_merge($route, ['params' => $params]);
        }
    }

    throw new RouteNotFound();
}

private function toRegex(string $uri): string
{
    $parts = preg_split('/(\{[a-zA-Z_][a-zA-Z0-9_]*\})/', $uri, -1, PREG_SPLIT_DELIM_CAPTURE);
    $pattern = '';
    foreach ($parts as $part) {
        if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $part, $m)) {
            $pattern .= '(?P<' . $m[1] . '>[^/]+)';
        } else {
            $pattern .= preg_quote($part, '#');
        }
    }
    return '#^' . $pattern . '$#';
}
```

- [ ] **Step 4: Run full test suite — verify no regressions**

```bash
./vendor/bin/phpunit --colors
```

Expected: all tests pass, including the new ones added in Step 1.

- [ ] **Step 5: Commit**

```bash
git add app/Support/Router.php tests/Support/RouterTest.php
git commit -m "feat: add dynamic param matching to Router"
```

---

## Task 2: Dispatcher — FQCN Fix + Param Forwarding + Return Response

**Files:**
- Modify: `app/Support/Dispatcher.php`

**Context:** Two bugs exist today.

1. **Double-namespace bug:** routes store controllers as FQCN (e.g., `App\Http\Controllers\CursosController` from `CursosController::class`), but Dispatcher prepends `App\Http\Controllers\` again, creating an invalid class name. This was never caught because no integration tests exist.

2. **No param forwarding:** `$controller->$action()` is called with no args, so `destroy(int $id)` never receives `$id` from the URL.

- [ ] **Step 1: Rewrite `dispatch()` in Dispatcher.php**

Replace only the `dispatch()` method — keep constructor and constants unchanged:

```php
/**
 * Resolve e executa a action do controller indicado pela rota.
 *
 * Aceita tanto FQCN completo (quando registrado via Controller::class) quanto
 * nome curto (quando usado com controllerNamespace customizado, como em testes).
 * Os params extraídos pelo Router são repassados como args posicionais ao action.
 *
 * @param array{controller: string, action: string, params: array<string, string>} $route
 *
 * @throws \RuntimeException Quando o método de action não existe no controller.
 */
public function dispatch(array $route): ?ResponseInterface
{
    $fqcn = str_contains($route['controller'], '\\')
        ? $route['controller']
        : $this->controllerNamespace . $route['controller'];
    $action = $route['action'];

    $controller = $this->container->get($fqcn);

    if (!method_exists($controller, $action)) {
        throw new \RuntimeException("Action '{$action}' not found on controller '{$fqcn}'.");
    }

    $result = $controller->$action(...array_values($route['params'] ?? []));

    return $result instanceof ResponseInterface ? $result : null;
}
```

- [ ] **Step 2: Run full test suite — verify no regressions**

```bash
./vendor/bin/phpunit --colors
```

Expected: all tests pass. The DispatcherTest uses short names + custom namespace, so `str_contains(..., '\\')` is false for `FakeController` → it still prepends the test namespace correctly.

- [ ] **Step 3: Commit**

```bash
git add app/Support/Dispatcher.php
git commit -m "fix: resolve FQCN directly in Dispatcher and forward route params to actions"
```

---

## Task 3: index.php — Call send() on Returned Response

**Files:**
- Modify: `index.php`

**Context:** Dispatcher no longer calls `send()` internally — it returns `?ResponseInterface`. index.php must call `send()` on the returned value.

- [ ] **Step 1: Update the dispatch call in index.php**

Find the line that calls `$dispatcher->dispatch($route)` and change it to:

```php
$dispatcher->dispatch($route)?->send();
```

The `?->` null-safe operator handles the case where dispatch returns null (action returned void).

- [ ] **Step 2: Run full test suite**

```bash
./vendor/bin/phpunit --colors
```

Expected: all tests pass.

- [ ] **Step 3: Commit**

```bash
git add index.php
git commit -m "fix: call send() on response returned by Dispatcher"
```

---

## Task 4: routes/api.php — Return Router + New Listing Routes

**Files:**
- Modify: `routes/api.php`

- [ ] **Step 1: Add `return $router;` and 3 new routes to routes/api.php**

Add the following routes after the existing route registrations, then add `return $router;` as the last line:

```php
$router->get('/usuarios', [UsuariosController::class, 'index']);
$router->get('/turmas', [TurmasController::class, 'index']);
$router->get('/cursos/{cursoId}/turmas', [TurmasController::class, 'indexByCurso']);

return $router;
```

The full file after the change should end with these 3 new routes + `return $router;`. The `return` is required so that `$router = require 'routes/api.php'` works in index.php and in integration test helpers.

- [ ] **Step 2: Run full test suite**

```bash
./vendor/bin/phpunit --colors
```

Expected: all tests pass.

- [ ] **Step 3: Commit**

```bash
git add routes/api.php
git commit -m "feat: add listing routes for usuarios and turmas, return router from routes file"
```

---

## Task 5: TurmaRepository — Add curso_id Filter to get()

**Files:**
- Modify: `app/Repositories/TurmaRepository.php`

**Context:** `TurmaRepository::get()` currently ignores the `$filters` array and fetches all turmas. `TurmaService::get(['curso_id' => $id])` is called by the new `indexByCurso` controller method — the filter must be applied.

- [ ] **Step 1: Add a failing test to TurmaRepositoryTest**

Open `tests/Repositories/TurmaRepositoryTest.php` and add:

```php
public function test_get_filters_by_curso_id(): void
{
    // This test will fail until TurmaRepository::get() applies the filter.
    // Run it after implementation to confirm it passes.
    $this->markTestIncomplete('Implement after updating TurmaRepository::get()');
}
```

(This acts as a placeholder — we write the real test after checking what TurmaRepositoryTest currently looks like, since it may mock the PDO. The integration tests in Task 11 cover this end-to-end.)

- [ ] **Step 2: Replace `get()` in TurmaRepository.php**

Replace only the `get()` method — keep all other methods unchanged:

```php
public function get(array $filters = []): array
{
    $sql    = 'SELECT * FROM turmas';
    $params = [];

    if (!empty($filters['curso_id'])) {
        $sql    .= ' WHERE curso_id = ?';
        $params[] = $filters['curso_id'];
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);

    return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
}
```

- [ ] **Step 3: Remove the markTestIncomplete and write a real test (if TurmaRepositoryTest uses a real PDO)**

Open `tests/Repositories/TurmaRepositoryTest.php`. If the test already uses a real/mock PDO setup that allows SQL execution, replace the placeholder with:

```php
public function test_get_filters_by_curso_id(): void
{
    // Insert 2 turmas under different cursos, assert only the matching one is returned.
    // Adapt to whatever PDO/fixture setup TurmaRepositoryTest already uses.
}
```

If TurmaRepositoryTest uses mocks that don't execute real SQL, leave the placeholder with `markTestIncomplete` — the integration tests in Task 11 cover this behavior end-to-end.

- [ ] **Step 4: Run full test suite**

```bash
./vendor/bin/phpunit --colors
```

Expected: all tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Repositories/TurmaRepository.php tests/Repositories/TurmaRepositoryTest.php
git commit -m "feat: add curso_id filter to TurmaRepository::get()"
```

---

## Task 6: UsuariosController — Add index() Method + Unit Test

**Files:**
- Modify: `app/Http/Controllers/UsuariosController.php`
- Modify: `tests/Http/Controllers/UsuariosControllerTest.php`

- [ ] **Step 1: Add failing test to UsuariosControllerTest**

Open `tests/Http/Controllers/UsuariosControllerTest.php`. Add this helper and test method (keep existing tests):

```php
private function makeUsuario(int $id = 1): Usuario
{
    $u = new Usuario('Alice', new \App\ValueObjects\Email('alice@example.com'));
    $u->setId($id);
    return $u;
}

public function test_index_returns_200_with_list(): void
{
    $this->service->method('get')->willReturn([$this->makeUsuario()]);
    $response = $this->controller->index();
    $this->assertSame(200, $response->getStatusForTest());
}
```

Also add the missing `use` statements at the top if not already present:
```php
use App\Entities\Usuario;
use App\ValueObjects\Email;
```

- [ ] **Step 2: Run the new test — verify it FAILS**

```bash
./vendor/bin/phpunit tests/Http/Controllers/UsuariosControllerTest.php --colors
```

Expected: FAIL — `index()` method does not exist on UsuariosController.

- [ ] **Step 3: Add index() to UsuariosController.php**

Add this method to the class (keep existing methods unchanged):

```php
public function index(): ResponseInterface
{
    return $this->response()->success(['data' => $this->service->get()]);
}
```

- [ ] **Step 4: Run the test — verify it PASSES**

```bash
./vendor/bin/phpunit tests/Http/Controllers/UsuariosControllerTest.php --colors
```

Expected: all tests pass.

- [ ] **Step 5: Run full suite**

```bash
./vendor/bin/phpunit --colors
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/UsuariosController.php tests/Http/Controllers/UsuariosControllerTest.php
git commit -m "feat: add index listing endpoint for usuarios"
```

---

## Task 7: TurmasController — Add index() + indexByCurso() + Unit Tests

**Files:**
- Modify: `app/Http/Controllers/TurmasController.php`
- Modify: `tests/Http/Controllers/TurmasControllerTest.php`

- [ ] **Step 1: Add failing tests to TurmasControllerTest**

Open `tests/Http/Controllers/TurmasControllerTest.php`. Add these test methods (keep existing ones):

```php
public function test_index_returns_200_with_list(): void
{
    $this->service->method('get')->willReturn([$this->makeTurma()]);
    $response = $this->controller->index();
    $this->assertSame(200, $response->getStatusForTest());
}

public function test_index_by_curso_returns_200_with_list(): void
{
    $this->service->method('get')->willReturn([$this->makeTurma()]);
    $response = $this->controller->indexByCurso(1);
    $this->assertSame(200, $response->getStatusForTest());
}
```

- [ ] **Step 2: Run the new tests — verify they FAIL**

```bash
./vendor/bin/phpunit tests/Http/Controllers/TurmasControllerTest.php --colors
```

Expected: FAIL — `index()` and `indexByCurso()` don't exist.

- [ ] **Step 3: Add index() and indexByCurso() to TurmasController.php**

Add these methods (keep existing methods unchanged):

```php
public function index(): ResponseInterface
{
    return $this->response()->success(['data' => $this->service->get()]);
}

public function indexByCurso(int $cursoId): ResponseInterface
{
    return $this->response()->success(['data' => $this->service->get(['curso_id' => $cursoId])]);
}
```

- [ ] **Step 4: Run the tests — verify they PASS**

```bash
./vendor/bin/phpunit tests/Http/Controllers/TurmasControllerTest.php --colors
```

Expected: all tests pass.

- [ ] **Step 5: Run full suite**

```bash
./vendor/bin/phpunit --colors
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/TurmasController.php tests/Http/Controllers/TurmasControllerTest.php
git commit -m "feat: add index and indexByCurso listing endpoints for turmas"
```

---

## Task 8: Integration Test Infrastructure

**Files:**
- Create: `tests/Integration/schema.sql`
- Create: `tests/Integration/IntegrationTestCase.php`
- Modify: `phpunit.xml`

- [ ] **Step 1: Create tests/Integration/schema.sql**

```sql
CREATE TABLE IF NOT EXISTS usuarios (
    id    INTEGER PRIMARY KEY AUTOINCREMENT,
    nome  TEXT    NOT NULL,
    email TEXT    NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS cursos (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo     TEXT NOT NULL,
    descricao  TEXT NOT NULL,
    tema       TEXT NOT NULL,
    imagem_url TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS turmas (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    curso_id         INTEGER NOT NULL,
    titulo           TEXT    NOT NULL,
    descricao        TEXT    NOT NULL,
    quantidade_vagas INTEGER NOT NULL,
    status           TEXT    NOT NULL,
    data_inicio      TEXT    NOT NULL,
    data_fim         TEXT    NOT NULL,
    FOREIGN KEY (curso_id) REFERENCES cursos(id)
);

CREATE TABLE IF NOT EXISTS matriculas (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    turma_id   INTEGER NOT NULL,
    status     TEXT    NOT NULL DEFAULT 'ativo',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (turma_id)   REFERENCES turmas(id)
);
```

- [ ] **Step 2: Create tests/Integration/IntegrationTestCase.php**

```php
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

abstract class IntegrationTestCase extends TestCase
{
    protected Container $container;
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
        $this->bootContainer();
    }

    private function bootContainer(): void
    {
        $pdo = $this->pdo;
        $container = new Container();

        $container->singleton(PDO::class, fn() => $pdo);
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
            fn(Container $c) => new CursoService($c->get(CursoRepositoryInterface::class))
        );
        $container->bind(
            TurmaServiceInterface::class,
            fn(Container $c) => new TurmaService(
                $c->get(TurmaRepositoryInterface::class),
                $c->get(CursoRepositoryInterface::class),
                $c->get(MatriculaRepositoryInterface::class)
            )
        );
        $container->bind(
            UsuarioServiceInterface::class,
            fn(Container $c) => new UsuarioService($c->get(UsuarioRepositoryInterface::class))
        );
        $container->bind(
            MatriculaServiceInterface::class,
            fn(Container $c) => new MatriculaService(
                $c->get(MatriculaRepositoryInterface::class),
                $c->get(TurmaRepositoryInterface::class)
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
            return (new Response())->unprocessableEntity(['error' => $e->getMessage()]);
        }
    }
}
```

- [ ] **Step 3: Add Integration testsuite to phpunit.xml**

Open `phpunit.xml` and add an Integration testsuite entry inside `<testsuites>`:

```xml
<testsuite name="Integration">
    <directory>tests/Integration</directory>
</testsuite>
```

The final `<testsuites>` block should look like:

```xml
<testsuites>
    <testsuite name="Unit">
        <directory>tests</directory>
        <exclude>tests/Integration</exclude>
    </testsuite>
    <testsuite name="Integration">
        <directory>tests/Integration</directory>
    </testsuite>
</testsuites>
```

Also add `<exclude>tests/Integration</exclude>` to the Unit testsuite so integration tests don't run in the default suite.

- [ ] **Step 4: Verify infrastructure loads without errors**

```bash
./vendor/bin/phpunit --list-tests --testsuite=Integration 2>&1 | head -5
```

Expected: no PHP errors, output shows "No tests found" or an empty list (no test classes yet).

- [ ] **Step 5: Run full test suite — verify no regressions**

```bash
./vendor/bin/phpunit --colors
```

Expected: all existing tests pass.

- [ ] **Step 6: Commit**

```bash
git add tests/Integration/schema.sql tests/Integration/IntegrationTestCase.php phpunit.xml
git commit -m "test: add integration test infrastructure with SQLite in-memory base"
```

---

## Task 9: CursosIntegrationTest

**Files:**
- Create: `tests/Integration/CursosIntegrationTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

namespace Tests\Integration;

class CursosIntegrationTest extends IntegrationTestCase
{
    private function cursosPayload(string $titulo = 'PHP Avançado'): array
    {
        return [
            'titulo'     => $titulo,
            'descricao'  => 'Aprenda PHP moderno',
            'tema'       => 'tecnologia',
            'imagem_url' => 'https://example.com/img.jpg',
        ];
    }

    public function test_index_returns_200_with_empty_list(): void
    {
        $r = $this->dispatch('GET', '/api/v1/cursos');
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertSame([], $r->getDataForTest()['data']);
    }

    public function test_store_returns_201_with_created_curso(): void
    {
        $r = $this->dispatch('POST', '/api/v1/cursos', $this->cursosPayload());
        $this->assertSame(201, $r->getStatusForTest());
        $data = $r->getDataForTest()['data'];
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('PHP Avançado', $data['titulo']);
    }

    public function test_index_returns_created_curso(): void
    {
        $this->dispatch('POST', '/api/v1/cursos', $this->cursosPayload());
        $r = $this->dispatch('GET', '/api/v1/cursos');
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertCount(1, $r->getDataForTest()['data']);
    }

    public function test_update_returns_200_with_updated_data(): void
    {
        $created = $this->dispatch('POST', '/api/v1/cursos', $this->cursosPayload());
        $id = $created->getDataForTest()['data']['id'];

        $r = $this->dispatch('PUT', "/api/v1/cursos/{$id}", ['titulo' => 'Go Avançado']);
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertSame('Go Avançado', $r->getDataForTest()['data']['titulo']);
    }

    public function test_destroy_returns_204(): void
    {
        $created = $this->dispatch('POST', '/api/v1/cursos', $this->cursosPayload());
        $id = $created->getDataForTest()['data']['id'];

        $r = $this->dispatch('DELETE', "/api/v1/cursos/{$id}");
        $this->assertSame(204, $r->getStatusForTest());
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $r = $this->dispatch('PUT', '/api/v1/cursos/999', ['titulo' => 'X']);
        $this->assertSame(404, $r->getStatusForTest());
    }

    public function test_destroy_nonexistent_returns_404(): void
    {
        $r = $this->dispatch('DELETE', '/api/v1/cursos/999');
        $this->assertSame(404, $r->getStatusForTest());
    }
}
```

- [ ] **Step 2: Run the integration tests**

```bash
./vendor/bin/phpunit tests/Integration/CursosIntegrationTest.php --colors
```

Expected: all 7 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Integration/CursosIntegrationTest.php
git commit -m "test: add integration tests for Cursos CRUD"
```

---

## Task 10: UsuariosIntegrationTest

**Files:**
- Create: `tests/Integration/UsuariosIntegrationTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

namespace Tests\Integration;

class UsuariosIntegrationTest extends IntegrationTestCase
{
    private function usuariosPayload(string $email = 'alice@example.com'): array
    {
        return [
            'nome'  => 'Alice',
            'email' => $email,
        ];
    }

    public function test_index_returns_200_with_empty_list(): void
    {
        $r = $this->dispatch('GET', '/api/v1/usuarios');
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertSame([], $r->getDataForTest()['data']);
    }

    public function test_store_returns_201_with_created_usuario(): void
    {
        $r = $this->dispatch('POST', '/api/v1/usuarios', $this->usuariosPayload());
        $this->assertSame(201, $r->getStatusForTest());
        $data = $r->getDataForTest()['data'];
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('Alice', $data['nome']);
        $this->assertSame('alice@example.com', $data['email']);
    }

    public function test_index_returns_created_usuario(): void
    {
        $this->dispatch('POST', '/api/v1/usuarios', $this->usuariosPayload());
        $r = $this->dispatch('GET', '/api/v1/usuarios');
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertCount(1, $r->getDataForTest()['data']);
    }

    public function test_destroy_returns_204(): void
    {
        $created = $this->dispatch('POST', '/api/v1/usuarios', $this->usuariosPayload());
        $id = $created->getDataForTest()['data']['id'];

        $r = $this->dispatch('DELETE', "/api/v1/usuarios/{$id}");
        $this->assertSame(204, $r->getStatusForTest());
    }

    public function test_store_duplicate_email_returns_422(): void
    {
        $this->dispatch('POST', '/api/v1/usuarios', $this->usuariosPayload());
        $r = $this->dispatch('POST', '/api/v1/usuarios', $this->usuariosPayload());
        $this->assertSame(422, $r->getStatusForTest());
    }

    public function test_store_invalid_email_returns_422(): void
    {
        $r = $this->dispatch('POST', '/api/v1/usuarios', [
            'nome'  => 'Bob',
            'email' => 'not-an-email',
        ]);
        $this->assertSame(422, $r->getStatusForTest());
    }

    public function test_destroy_nonexistent_returns_404(): void
    {
        $r = $this->dispatch('DELETE', '/api/v1/usuarios/999');
        $this->assertSame(404, $r->getStatusForTest());
    }
}
```

- [ ] **Step 2: Run the integration tests**

```bash
./vendor/bin/phpunit tests/Integration/UsuariosIntegrationTest.php --colors
```

Expected: all 7 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Integration/UsuariosIntegrationTest.php
git commit -m "test: add integration tests for Usuarios CRUD and listing"
```

---

## Task 11: TurmasIntegrationTest

**Files:**
- Create: `tests/Integration/TurmasIntegrationTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

namespace Tests\Integration;

class TurmasIntegrationTest extends IntegrationTestCase
{
    private int $cursoId;

    protected function setUp(): void
    {
        parent::setUp();
        $r = $this->dispatch('POST', '/api/v1/cursos', [
            'titulo'     => 'Curso Base',
            'descricao'  => 'desc',
            'tema'       => 'tecnologia',
            'imagem_url' => 'img.jpg',
        ]);
        $this->cursoId = $r->getDataForTest()['data']['id'];
    }

    private function turmaPayload(): array
    {
        return [
            'titulo'           => 'Turma 1',
            'descricao'        => 'desc turma',
            'quantidade_vagas' => 30,
            'status'           => 'disponivel',
            'data_inicio'      => '2026-06-01',
            'data_fim'         => '2026-12-01',
        ];
    }

    public function test_store_returns_201(): void
    {
        $r = $this->dispatch('POST', "/api/v1/cursos/{$this->cursoId}/turmas", $this->turmaPayload());
        $this->assertSame(201, $r->getStatusForTest());
        $data = $r->getDataForTest()['data'];
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('Turma 1', $data['titulo']);
    }

    public function test_index_returns_all_turmas(): void
    {
        $this->dispatch('POST', "/api/v1/cursos/{$this->cursoId}/turmas", $this->turmaPayload());
        $r = $this->dispatch('GET', '/api/v1/turmas');
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertCount(1, $r->getDataForTest()['data']);
    }

    public function test_index_by_curso_returns_only_curso_turmas(): void
    {
        // Create turma under our curso
        $this->dispatch('POST', "/api/v1/cursos/{$this->cursoId}/turmas", $this->turmaPayload());

        // Create a second curso and its turma
        $r2 = $this->dispatch('POST', '/api/v1/cursos', [
            'titulo'     => 'Outro Curso',
            'descricao'  => 'desc',
            'tema'       => 'tecnologia',
            'imagem_url' => 'img.jpg',
        ]);
        $outroCursoId = $r2->getDataForTest()['data']['id'];
        $this->dispatch('POST', "/api/v1/cursos/{$outroCursoId}/turmas", array_merge($this->turmaPayload(), ['titulo' => 'Turma Outro']));

        // Index by first curso must return only 1 turma
        $r = $this->dispatch('GET', "/api/v1/cursos/{$this->cursoId}/turmas");
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertCount(1, $r->getDataForTest()['data']);
        $this->assertSame('Turma 1', $r->getDataForTest()['data'][0]['titulo']);
    }

    public function test_update_returns_200(): void
    {
        $created = $this->dispatch('POST', "/api/v1/cursos/{$this->cursoId}/turmas", $this->turmaPayload());
        $id = $created->getDataForTest()['data']['id'];

        $r = $this->dispatch('PUT', "/api/v1/turmas/{$id}", array_merge($this->turmaPayload(), ['titulo' => 'Turma Atualizada']));
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertSame('Turma Atualizada', $r->getDataForTest()['data']['titulo']);
    }

    public function test_destroy_returns_204(): void
    {
        $created = $this->dispatch('POST', "/api/v1/cursos/{$this->cursoId}/turmas", $this->turmaPayload());
        $id = $created->getDataForTest()['data']['id'];

        $r = $this->dispatch('DELETE', "/api/v1/turmas/{$id}");
        $this->assertSame(204, $r->getStatusForTest());
    }

    public function test_store_with_nonexistent_curso_returns_404(): void
    {
        $r = $this->dispatch('POST', '/api/v1/cursos/9999/turmas', $this->turmaPayload());
        $this->assertSame(404, $r->getStatusForTest());
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $r = $this->dispatch('PUT', '/api/v1/turmas/9999', $this->turmaPayload());
        $this->assertSame(404, $r->getStatusForTest());
    }
}
```

- [ ] **Step 2: Run the integration tests**

```bash
./vendor/bin/phpunit tests/Integration/TurmasIntegrationTest.php --colors
```

Expected: all 7 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Integration/TurmasIntegrationTest.php
git commit -m "test: add integration tests for Turmas CRUD and listing by curso"
```

---

## Task 12: MatriculasIntegrationTest

**Files:**
- Create: `tests/Integration/MatriculasIntegrationTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

namespace Tests\Integration;

class MatriculasIntegrationTest extends IntegrationTestCase
{
    private int $usuarioId;
    private int $turmaId;

    protected function setUp(): void
    {
        parent::setUp();

        $curso = $this->dispatch('POST', '/api/v1/cursos', [
            'titulo'     => 'Curso',
            'descricao'  => 'desc',
            'tema'       => 'tecnologia',
            'imagem_url' => 'img.jpg',
        ]);
        $cursoId = $curso->getDataForTest()['data']['id'];

        $turma = $this->dispatch('POST', "/api/v1/cursos/{$cursoId}/turmas", [
            'titulo'           => 'Turma',
            'descricao'        => 'desc',
            'quantidade_vagas' => 10,
            'status'           => 'disponivel',
            'data_inicio'      => '2026-06-01',
            'data_fim'         => '2026-12-01',
        ]);
        $this->turmaId = $turma->getDataForTest()['data']['id'];

        $usuario = $this->dispatch('POST', '/api/v1/usuarios', [
            'nome'  => 'Alice',
            'email' => 'alice@example.com',
        ]);
        $this->usuarioId = $usuario->getDataForTest()['data']['id'];
    }

    public function test_store_returns_201(): void
    {
        $r = $this->dispatch('POST', '/api/v1/matriculas', [
            'usuario_id' => $this->usuarioId,
            'turma_id'   => $this->turmaId,
        ]);
        $this->assertSame(201, $r->getStatusForTest());
        $this->assertArrayHasKey('id', $r->getDataForTest()['data']);
    }

    public function test_index_by_usuario_returns_matriculas(): void
    {
        $this->dispatch('POST', '/api/v1/matriculas', [
            'usuario_id' => $this->usuarioId,
            'turma_id'   => $this->turmaId,
        ]);

        $r = $this->dispatch('GET', "/api/v1/usuarios/{$this->usuarioId}/matriculas");
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertCount(1, $r->getDataForTest()['data']);
    }

    public function test_destroy_returns_204(): void
    {
        $created = $this->dispatch('POST', '/api/v1/matriculas', [
            'usuario_id' => $this->usuarioId,
            'turma_id'   => $this->turmaId,
        ]);
        $id = $created->getDataForTest()['data']['id'];

        $r = $this->dispatch('DELETE', "/api/v1/matriculas/{$id}");
        $this->assertSame(204, $r->getStatusForTest());
    }

    public function test_store_with_nonexistent_turma_returns_404(): void
    {
        $r = $this->dispatch('POST', '/api/v1/matriculas', [
            'usuario_id' => $this->usuarioId,
            'turma_id'   => 9999,
        ]);
        $this->assertSame(404, $r->getStatusForTest());
    }
}
```

- [ ] **Step 2: Run the integration tests**

```bash
./vendor/bin/phpunit tests/Integration/MatriculasIntegrationTest.php --colors
```

Expected: all 4 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Integration/MatriculasIntegrationTest.php
git commit -m "test: add integration tests for Matriculas"
```

---

## Task 13: BusinessFlowIntegrationTest

**Files:**
- Create: `tests/Integration/BusinessFlowIntegrationTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

namespace Tests\Integration;

class BusinessFlowIntegrationTest extends IntegrationTestCase
{
    public function test_full_enrollment_and_turma_closure_inactivates_matricula(): void
    {
        // 1. Create curso
        $r = $this->dispatch('POST', '/api/v1/cursos', [
            'titulo'     => 'PHP Avançado',
            'descricao'  => 'Curso completo de PHP',
            'tema'       => 'tecnologia',
            'imagem_url' => 'https://example.com/php.jpg',
        ]);
        $this->assertSame(201, $r->getStatusForTest());
        $cursoId = $r->getDataForTest()['data']['id'];

        // 2. Create turma under the curso
        $r = $this->dispatch('POST', "/api/v1/cursos/{$cursoId}/turmas", [
            'titulo'           => 'Turma Janeiro',
            'descricao'        => 'Turma do primeiro semestre',
            'quantidade_vagas' => 30,
            'status'           => 'disponivel',
            'data_inicio'      => '2026-01-01',
            'data_fim'         => '2026-06-30',
        ]);
        $this->assertSame(201, $r->getStatusForTest());
        $turmaId = $r->getDataForTest()['data']['id'];

        // 3. Create usuario
        $r = $this->dispatch('POST', '/api/v1/usuarios', [
            'nome'  => 'João Silva',
            'email' => 'joao@example.com',
        ]);
        $this->assertSame(201, $r->getStatusForTest());
        $usuarioId = $r->getDataForTest()['data']['id'];

        // 4. Matriculate usuario in turma
        $r = $this->dispatch('POST', '/api/v1/matriculas', [
            'usuario_id' => $usuarioId,
            'turma_id'   => $turmaId,
        ]);
        $this->assertSame(201, $r->getStatusForTest());
        $matriculaStatus = $r->getDataForTest()['data']['status'];
        $this->assertSame('ativo', $matriculaStatus);

        // 5. Encerrar turma — must inactivate all matriculas
        $r = $this->dispatch('PUT', "/api/v1/turmas/{$turmaId}", [
            'titulo'           => 'Turma Janeiro',
            'descricao'        => 'Turma do primeiro semestre',
            'quantidade_vagas' => 30,
            'status'           => 'encerrado',
            'data_inicio'      => '2026-01-01',
            'data_fim'         => '2026-06-30',
        ]);
        $this->assertSame(200, $r->getStatusForTest());
        $this->assertSame('encerrado', $r->getDataForTest()['data']['status']);

        // 6. Verify matricula is now inativo
        $r = $this->dispatch('GET', "/api/v1/usuarios/{$usuarioId}/matriculas");
        $this->assertSame(200, $r->getStatusForTest());
        $matriculas = $r->getDataForTest()['data'];
        $this->assertCount(1, $matriculas);
        $this->assertSame('inativo', $matriculas[0]['status']);
    }

    public function test_listing_endpoints_reflect_correct_data(): void
    {
        // Create 2 cursos, 1 turma each, 1 usuario — verify listings
        $this->dispatch('POST', '/api/v1/cursos', [
            'titulo' => 'Curso A', 'descricao' => 'desc', 'tema' => 'tecnologia', 'imagem_url' => 'a.jpg',
        ]);
        $rB = $this->dispatch('POST', '/api/v1/cursos', [
            'titulo' => 'Curso B', 'descricao' => 'desc', 'tema' => 'tecnologia', 'imagem_url' => 'b.jpg',
        ]);
        $cursoBId = $rB->getDataForTest()['data']['id'];

        $this->dispatch('POST', '/api/v1/usuarios', ['nome' => 'Maria', 'email' => 'maria@example.com']);

        // Verify listings count correctly
        $this->assertCount(2, $this->dispatch('GET', '/api/v1/cursos')->getDataForTest()['data']);
        $this->assertCount(1, $this->dispatch('GET', '/api/v1/usuarios')->getDataForTest()['data']);
        $this->assertCount(0, $this->dispatch('GET', '/api/v1/turmas')->getDataForTest()['data']);

        // Add turma to Curso B and verify scoped listing
        $this->dispatch('POST', "/api/v1/cursos/{$cursoBId}/turmas", [
            'titulo' => 'T1', 'descricao' => 'desc', 'quantidade_vagas' => 5,
            'status' => 'disponivel', 'data_inicio' => '2026-07-01', 'data_fim' => '2026-12-31',
        ]);
        $this->assertCount(1, $this->dispatch('GET', '/api/v1/turmas')->getDataForTest()['data']);
        $this->assertCount(1, $this->dispatch('GET', "/api/v1/cursos/{$cursoBId}/turmas")->getDataForTest()['data']);
    }
}
```

- [ ] **Step 2: Run all integration tests**

```bash
./vendor/bin/phpunit --testsuite=Integration --colors
```

Expected: all integration tests pass.

- [ ] **Step 3: Run full test suite (unit + integration)**

```bash
./vendor/bin/phpunit --colors
```

Expected: all tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/Integration/BusinessFlowIntegrationTest.php
git commit -m "test: add business flow integration test covering full enrollment lifecycle"
```
