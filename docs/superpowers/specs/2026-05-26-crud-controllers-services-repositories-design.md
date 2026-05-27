# Design: CRUD Controllers, Services & Repositories

**Date:** 2026-05-26  
**Scope:** Cursos, Turmas, Usuários, Matrículas — full CRUD implementation on top of existing micro-framework

---

## 1. Context

Base framework is complete: Container (PSR-11), Router, Dispatcher, Request, Response, ExceptionHandler. Entities (Turma, Curso, Usuário), service interfaces, and repository interfaces already exist. This spec covers everything needed to make the API functional.

---

## 2. Use Cases Covered

1. Create, update, delete a Curso (título, descrição, tema, URL de imagem)
2. Create, update, delete a Turma for a Curso (título, descrição, vagas, status, data início/fim)
3. Create and delete a Usuário (nome, email)
4. List Cursos with available Turmas — filters by título and tema
5. Enroll a Usuário in an available Turma
6. Reject enrollment in closed Turmas or outside date range
7. Reject duplicate enrollment (same Usuário, same Curso)
8. List all Cursos a Usuário is enrolled in

---

## 3. Entity Changes

All entities gain `?int $id = null` as a trailing constructor parameter. `null` means not yet persisted; populated by the repository after INSERT.

**Turma** also gains `int $cursoId` — required for ownership enforcement and the same-course duplicate enrollment check.

### 3.1 Matricula (new entity)

```php
class Matricula {
    public function __construct(
        private int $usuarioId,
        private int $turmaId,
        private int $cursoId,
        private ?int $id = null
    ) {}
}
```

---

## 4. Database Schema

| Table | Columns |
|---|---|
| `cursos` | `id` PK, `titulo`, `descricao`, `tema` (VARCHAR/enum), `url_imagem` |
| `turmas` | `id` PK, `curso_id` FK, `titulo`, `descricao`, `quantidade_vagas`, `status` (disponivel/encerrado), `data_inicio` DATE, `data_fim` DATE |
| `usuarios` | `id` PK, `nome`, `email` UNIQUE |
| `matriculas` | `id` PK, `usuario_id` FK, `turma_id` FK, `curso_id` FK, UNIQUE(`usuario_id`, `curso_id`) |

---

## 5. New Contracts

### MatriculaRepositoryInterface
```php
public function store(Matricula $matricula): Matricula;
public function findByUsuarioAndCurso(int $usuarioId, int $cursoId): ?Matricula;
public function getByUsuario(int $usuarioId): array;
public function destroy(int $id): void;
```

### MatriculaServiceInterface
```php
public function enroll(int $usuarioId, int $turmaId): Matricula;
public function getByUsuario(int $usuarioId): array; // returns Curso[]
```

---

## 6. Repository Layer

### BaseRepository
```php
abstract class BaseRepository {
    public function __construct(protected PDO $pdo) {}
}
```

PDO registered as singleton in container, injected into all repos.

### CursoRepository
- Standard: `get`, `find`, `store`, `update`, `destroy`
- Extra: `getWithAvailableTurmas(array $filters)` — single JOIN query:
  `SELECT cursos.* FROM cursos JOIN turmas ON turmas.curso_id = cursos.id WHERE turmas.status = 'disponivel' AND NOW() BETWEEN turmas.data_inicio AND turmas.data_fim`
  — optional `AND cursos.titulo LIKE ?` and `AND cursos.tema = ?` filters

### TurmaRepository
- Standard: `get`, `find`, `store`, `update`, `destroy`
- Extra: `findByCursoId(int $cursoId): array`

### UsuarioRepository
- Standard only: `get`, `find`, `store`, `update`, `destroy`

### MatriculaRepository
- `store(Matricula)` — INSERT INTO matriculas
- `findByUsuarioAndCurso(usuarioId, cursoId)` — used for duplicate enrollment check
- `getByUsuario(usuarioId)` — JOIN cursos via turmas, returns Curso[] with enrolled turma data
- `destroy(id)` — DELETE

---

## 7. Service Layer

### CursoService
Delegates to `CursoRepositoryInterface`. No additional business rules beyond field presence validation on `store`.

Extra method `getAvailable(array $filters)` proxies `repo->getWithAvailableTurmas(filters)` — called by `GET /cursos`.

### TurmaService
Delegates to `TurmaRepositoryInterface`. On `store`, validates that the parent Curso exists (calls `cursoRepo->find(cursoId)`).

### UsuarioService
Basic CRUD. No `update` route exists per spec, but interface method kept for future use.

### MatriculaService — enrollment rules (in order):
1. `turmaRepo->find(turmaId)` — throws `NotFound` if missing
2. `turma->status !== StatusTurma::Disponivel` → throw `UnprocessableEntity`
3. `now < turma->dataInicio || now > turma->dataFim` → throw `UnprocessableEntity`
4. `matriculaRepo->findByUsuarioAndCurso(usuarioId, turma->cursoId)` not null → throw `UnprocessableEntity` ("already enrolled in this course")
5. `matriculaRepo->store(new Matricula(usuarioId, turmaId, turma->cursoId))`

All exceptions caught by existing `ExceptionHandler`.

---

## 8. Controller Layer

All controllers extend `BaseController`, receive service via constructor injection.

### Pattern
```php
class CursosController extends BaseController {
    public function __construct(private CursoServiceInterface $service) {}
}
```

Request body read via `RequestInterface::getJSON()` (parses `application/json` body). Route params passed as method arguments by Dispatcher.

### CursosController
| Method | Route | Response |
|---|---|---|
| `index` | `GET /cursos` | 200 + array |
| `store` | `POST /cursos` | 201 + Curso |
| `update` | `PUT /cursos/{id}` | 200 + Curso |
| `destroy` | `DELETE /cursos/{id}` | 204 |

`index` passes `titulo` and `tema` query params as filters to `service->getAvailable()`.

### TurmasController
| Method | Route | Response |
|---|---|---|
| `store` | `POST /cursos/{cursoId}/turmas` | 201 + Turma |
| `update` | `PUT /turmas/{id}` | 200 + Turma |
| `destroy` | `DELETE /turmas/{id}` | 204 |

### UsuariosController
| Method | Route | Response |
|---|---|---|
| `store` | `POST /usuarios` | 201 + Usuario |
| `destroy` | `DELETE /usuarios/{id}` | 204 |

### MatriculasController
| Method | Route | Response |
|---|---|---|
| `store` | `POST /matriculas` | 201 + Matricula |
| `index` | `GET /usuarios/{id}/matriculas` | 200 + Curso[] |

---

## 9. Routes

```php
// Cursos
$router->get('/cursos', [CursosController::class, 'index']);
$router->post('/cursos', [CursosController::class, 'store']);
$router->put('/cursos/{id}', [CursosController::class, 'update']);
$router->delete('/cursos/{id}', [CursosController::class, 'destroy']);

// Turmas (nested create, flat update/delete)
$router->post('/cursos/{cursoId}/turmas', [TurmasController::class, 'store']);
$router->put('/turmas/{id}', [TurmasController::class, 'update']);
$router->delete('/turmas/{id}', [TurmasController::class, 'destroy']);

// Usuarios
$router->post('/usuarios', [UsuariosController::class, 'store']);
$router->delete('/usuarios/{id}', [UsuariosController::class, 'destroy']);

// Matriculas
$router->post('/matriculas', [MatriculasController::class, 'store']);
$router->get('/usuarios/{id}/matriculas', [MatriculasController::class, 'index']);
```

---

## 10. Container Bindings (AppServiceProvider additions)

```php
// PDO singleton
$this->container->singleton(PDO::class, fn(Container $c) => new PDO(
    $c->get(ConfigInterface::class)->get('db.dsn'),
    $c->get(ConfigInterface::class)->get('db.user'),
    $c->get(ConfigInterface::class)->get('db.pass'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
));

// Repositories
$this->container->bind(CursoRepositoryInterface::class, fn(Container $c) => new CursoRepository($c->get(PDO::class)));
$this->container->bind(TurmaRepositoryInterface::class, fn(Container $c) => new TurmaRepository($c->get(PDO::class)));
$this->container->bind(UsuarioRepositoryInterface::class, fn(Container $c) => new UsuarioRepository($c->get(PDO::class)));
$this->container->bind(MatriculaRepositoryInterface::class, fn(Container $c) => new MatriculaRepository($c->get(PDO::class)));

// Services
$this->container->bind(CursoServiceInterface::class, fn(Container $c) => new CursoService($c->get(CursoRepositoryInterface::class)));
$this->container->bind(TurmaServiceInterface::class, fn(Container $c) => new TurmaService($c->get(TurmaRepositoryInterface::class), $c->get(CursoRepositoryInterface::class)));
$this->container->bind(UsuarioServiceInterface::class, fn(Container $c) => new UsuarioService($c->get(UsuarioRepositoryInterface::class)));
$this->container->bind(MatriculaServiceInterface::class, fn(Container $c) => new MatriculaService($c->get(MatriculaRepositoryInterface::class), $c->get(TurmaRepositoryInterface::class)));
```

---

## 11. Out of Scope

- Database migrations (schema must be created manually or via a separate migration spec)
- Authentication / authorization
- Pagination
- Soft deletes
- UsuarioService::update (interface method exists, no route)
