# Design: Listing Endpoints + Integration Tests

## Context

A aplicação tem 4 entidades: Curso, Turma, Usuario, Matricula. Cursos já têm `GET /cursos`. Turmas, Usuarios e Matriculas carecem de endpoints de listagem completos. Além disso, todos os testes existentes são unit tests com mocks — não há cobertura de integração que valide o fluxo ponta a ponta (Router → Controller → Service → Repository → DB).

Há também um bug estrutural latente: o Router faz matching exato de string, mas as rotas registradas usam padrões `{id}` que nunca casam com URIs reais como `/cursos/1`. O Dispatcher também não passa params para os actions. Ambos precisam ser corrigidos como pré-requisito.

## Goals

1. Adicionar endpoints de listagem para Usuarios e Turmas.
2. Corrigir Router (dynamic param matching) e Dispatcher (param forwarding).
3. Criar suíte de testes de integração cobrindo CRUD completo, fluxo de negócio e casos de erro.

## Out of Scope

- Paginação ou filtros avançados nos novos endpoints de listagem.
- Testes de performance.
- Mudança no formato de resposta existente.

---

## Part 1: Novos Endpoints de Listagem

### Novas Rotas

```
GET /api/v1/usuarios                     → UsuariosController::index()
GET /api/v1/turmas                       → TurmasController::index()
GET /api/v1/cursos/{cursoId}/turmas      → TurmasController::indexByCurso(int $cursoId)
```

### Controllers

**UsuariosController::index():**
```php
public function index(): ResponseInterface
{
    return $this->response()->success(['data' => $this->service->get()]);
}
```

**TurmasController::index():**
```php
public function index(): ResponseInterface
{
    return $this->response()->success(['data' => $this->service->get()]);
}
```

**TurmasController::indexByCurso(int $cursoId):**
```php
public function indexByCurso(int $cursoId): ResponseInterface
{
    return $this->response()->success(['data' => $this->service->get(['curso_id' => $cursoId])]);
}
```

### Services / Repositories

`UsuarioService::get()` e `TurmaService::get()` já existem. `TurmaRepository::get()` já aceita filtros. Verificar se filtra por `curso_id` — se não, adicionar esse filtro ao repositório.

### Unit Tests dos Novos Métodos

Seguindo padrão existente (`CursosControllerTest`):
- `UsuariosControllerTest::test_index_returns_200_with_list()`
- `TurmasControllerTest::test_index_returns_200_with_list()`
- `TurmasControllerTest::test_index_by_curso_returns_200_with_list()`

---

## Part 2: Router Dynamic Params + Dispatcher Forwarding

### Problema

Router atual: `$route['uri'] === $uri` — match exato. `PUT /cursos/{id}` nunca casa com `/cursos/1`.

Dispatcher atual: `$controller->$action()` — sem args. `destroy(int $id)` nunca recebe o ID da URL.

### Solução: Router

Converter `{param}` em named capture group regex:

```
/api/v1/cursos/{id}  →  #^/api/v1/cursos/(?P<id>[^/]+)$#
```

`route(string $uri, string $method)` retorna também `params`:

```php
return [
    'uri'        => $route['uri'],
    'controller' => $route['controller'],
    'method'     => $route['method'],
    'action'     => $route['action'],
    'params'     => ['id' => '5'],   // extraído do match
];
```

Rotas sem params retornam `params: []`.

### Solução: Dispatcher

```php
$result = $controller->$action(...array_values($route['params']));
```

Params são passados em ordem posicional, consistente com como os unit tests já chamam controllers: `$controller->destroy(1)`.

### Compatibilidade

Unit tests existentes não usam o Router/Dispatcher — instanciam controllers diretamente. Sem regressão. `RouterTest` precisa ser atualizado para cobrir o novo matching.

---

## Part 3: Testes de Integração

### Infraestrutura

**`tests/Integration/IntegrationTestCase.php`** — base class:

1. `setUp()`: cria `PDO('sqlite::memory:')`, executa `tests/Integration/schema.sql` (DDL SQLite-compatível).
2. Boota Container com repositórios reais apontando para o PDO SQLite.
3. Registra Request e Response reais no container.
4. Expõe `dispatch(string $method, string $uri, array $body = [], array $query = [])`:
   - Popula `$_SERVER['REQUEST_METHOD']`, `$_SERVER['REQUEST_URI']`, `$_GET`, `$_POST`.
   - Instancia Router com as rotas de `routes/api.php`.
   - Chama `Router::route()` → `Dispatcher::dispatch()`.
   - Captura e retorna a Response.

**`tests/Integration/schema.sql`** — DDL SQLite-compatível (sem `UNSIGNED`, `ENGINE`, `CHARSET`):

```sql
CREATE TABLE cursos (...);
CREATE TABLE turmas (...);
CREATE TABLE usuarios (...);
CREATE TABLE matriculas (...);
```

### Cobertura por Arquivo

| Arquivo | Cenários |
|---|---|
| `CursosIntegrationTest` | index (lista vazia / com dados), store 201, update 200, destroy 204, update 404, destroy 404, store 422 |
| `TurmasIntegrationTest` | store 201, index (flat + por curso), update 200, destroy 204, store com curso inexistente 404 |
| `UsuariosIntegrationTest` | index (lista vazia / com dados), store 201, destroy 204, store email duplicado 422, store email inválido 422 |
| `MatriculasIntegrationTest` | store 201, index por usuario, destroy 204, store com turma inexistente 404 |
| `BusinessFlowIntegrationTest` | criar curso → criar turma → criar usuário → matricular → encerrar turma → verificar matrícula inativada |

### Exemplo de Teste

```php
public function test_business_flow(): void
{
    // Cria curso
    $r = $this->dispatch('POST', '/api/v1/cursos', [
        'titulo' => 'PHP Avançado', 'descricao' => 'desc',
        'tema' => 'tecnologia', 'imagem_url' => 'img.jpg',
    ]);
    $this->assertSame(201, $r->getStatusForTest());
    $cursoId = $r->getDataForTest()['data']['id'];

    // Cria turma
    $r = $this->dispatch('POST', "/api/v1/cursos/{$cursoId}/turmas", [...]);
    $turmaId = $r->getDataForTest()['data']['id'];

    // Cria usuário
    $r = $this->dispatch('POST', '/api/v1/usuarios', [...]);
    $usuarioId = $r->getDataForTest()['data']['id'];

    // Matricula
    $r = $this->dispatch('POST', '/api/v1/matriculas', [
        'usuario_id' => $usuarioId, 'turma_id' => $turmaId,
    ]);
    $this->assertSame(201, $r->getStatusForTest());

    // Encerra turma → matrícula deve ser inativada
    $this->dispatch('PUT', "/api/v1/turmas/{$turmaId}", ['status' => 'encerrado', ...]);

    $r = $this->dispatch('GET', "/api/v1/usuarios/{$usuarioId}/matriculas");
    $matriculas = $r->getDataForTest()['data'];
    $this->assertSame('inativo', $matriculas[0]['status']);
}
```

### Dependências

- `Response::getDataForTest()` — verificar se já existe ou adicionar (similar ao `getStatusForTest()`).
- `TurmaRepository::get()` — verificar se filtra por `curso_id`.

---

## Data Flow: Integração Completa

```
Test::dispatch(method, uri, body)
  → $_SERVER/$_GET/$_POST populados
  → Router::route(uri, method)           [regex matching → params extraídos]
  → Dispatcher::dispatch(route)          [params passados como args posicionais]
  → Controller::action(...params)        [usa Request/Response do container]
  → Service::method(...)
  → Repository::method(...)              [PDO SQLite in-memory]
  → Response retornada
```

## Error Handling

- 404: Router lança `RouteNotFound` (ou `NotFound`) → ExceptionHandler captura → 404 response.
- 422: Service/Repository lança `UnprocessableEntity` → ExceptionHandler captura → 422 response.
- Tests verificam ambos os caminhos de erro via `assertSame(404, $r->getStatusForTest())`.

## Implementation Order

1. Router dynamic param matching
2. Dispatcher param forwarding + RouterTest update
3. Novos endpoints (rotas + controller methods + unit tests)
4. `schema.sql` SQLite
5. `IntegrationTestCase` base
6. Testes de integração por entidade
7. `BusinessFlowIntegrationTest`
