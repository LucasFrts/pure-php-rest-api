# CRUD Controllers, Services & Repositories — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement full CRUD for Curso, Turma, Usuario and Matricula enrollment with PDO repositories, business-rule services, and HTTP controllers wired into the existing micro-framework.

**Architecture:** Services hold business rules and delegate data access to Repository interfaces (PDO). Controllers are thin: read request, call service, return typed Response. All domain exceptions implement `HttpExceptionInterface` so the existing `ExceptionHandler` renders them automatically.

**Tech Stack:** PHP 8.4, PDO (MySQL production / SQLite in-memory for tests), PHPUnit 11

---

## File Map

### Created
| Path | Responsibility |
|---|---|
| `app/Entities/Matricula.php` | Enrollment entity |
| `app/Exceptions/Http/NotFound.php` | 404 HTTP exception |
| `app/Exceptions/Http/UnprocessableEntity.php` | 422 HTTP exception |
| `app/Contracts/Repositories/MatriculaRepositoryInterface.php` | Enrollment repo contract |
| `app/Contracts/Services/MatriculaServiceInterface.php` | Enrollment service contract |
| `app/Repositories/BaseRepository.php` | PDO holder |
| `app/Repositories/CursoRepository.php` | Curso CRUD + available-turmas JOIN |
| `app/Repositories/TurmaRepository.php` | Turma CRUD + findByCursoId |
| `app/Repositories/UsuarioRepository.php` | Usuario CRUD |
| `app/Repositories/MatriculaRepository.php` | Enrollment CRUD + duplicate check |
| `app/Services/UsuarioService.php` | Usuario CRUD service |
| `app/Services/MatriculaService.php` | Enrollment with business-rule validation |
| `app/Http/Controllers/CursosController.php` | index/store/update/destroy |
| `app/Http/Controllers/UsuariosController.php` | store/destroy |
| `app/Http/Controllers/MatriculasController.php` | store (enroll) / index (by usuario) |
| `tests/Entities/MatriculaTest.php` | |
| `tests/Repositories/CursoRepositoryTest.php` | SQLite in-memory |
| `tests/Repositories/TurmaRepositoryTest.php` | SQLite in-memory |
| `tests/Repositories/UsuarioRepositoryTest.php` | SQLite in-memory |
| `tests/Repositories/MatriculaRepositoryTest.php` | SQLite in-memory |
| `tests/Services/CursoServiceTest.php` | mocked repo |
| `tests/Services/TurmaServiceTest.php` | mocked repo |
| `tests/Services/UsuarioServiceTest.php` | mocked repo |
| `tests/Services/MatriculaServiceTest.php` | mocked repo, enrollment rules |
| `tests/Http/Controllers/CursosControllerTest.php` | |
| `tests/Http/Controllers/TurmasControllerTest.php` | |
| `tests/Http/Controllers/UsuariosControllerTest.php` | |
| `tests/Http/Controllers/MatriculasControllerTest.php` | |

### Modified
| Path | Change |
|---|---|
| `app/ValueObjects/Email.php` | Add constructor + getValue() + __toString() |
| `app/Entities/StatusTurma.php` | Add fromString() + toString() |
| `app/Entities/Temas.php` | Add fromString() + toString() |
| `app/Entities/Turma.php` | Add cursoId, ?int id, getters, setId |
| `app/Entities/Curso.php` | Add ?int id, getters, setId |
| `app/Entities/Usuario.php` | Add ?int id, getters, setId |
| `app/Contracts/Repositories/CursoRepositoryInterface.php` | Add getWithAvailableTurmas |
| `app/Contracts/Repositories/TurmaRepositoryInterface.php` | Add findByCursoId |
| `app/Support/Container.php` | Add static setContainer() for test isolation |
| `app/Services/TurmaService.php` | Complete implementation |
| `app/Services/CursoService.php` | Complete implementation |
| `app/Http/Controllers/TurmasController.php` | Add store/update/destroy |
| `app/Providers/AppServiceProvider.php` | Bind PDO + repos + services |
| `routes/api.php` | All routes |
| `config.php` | Add db section |

---

## Task 1: Email VO + Enum Serialization Methods

**Files:**
- Modify: `app/ValueObjects/Email.php`
- Modify: `app/Entities/StatusTurma.php`
- Modify: `app/Entities/Temas.php`
- Test: `tests/ValueObjects/EmailTest.php`
- Test: `tests/Entities/StatusTurmaTest.php`
- Test: `tests/Entities/TemasTest.php`

- [ ] **Step 1: Write failing test for Email**

```php
<?php
// tests/ValueObjects/EmailTest.php
namespace Tests\ValueObjects;

use App\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function test_stores_valid_email(): void
    {
        $email = new Email('user@example.com');
        $this->assertSame('user@example.com', $email->getValue());
    }

    public function test_to_string_returns_address(): void
    {
        $email = new Email('user@example.com');
        $this->assertSame('user@example.com', (string) $email);
    }

    public function test_invalid_email_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('not-an-email');
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/ValueObjects/EmailTest.php
```
Expected: `Error: ... Email::__construct() ... too few arguments` or `Call to undefined method`.

- [ ] **Step 3: Implement Email**

```php
<?php
// app/ValueObjects/Email.php
namespace App\ValueObjects;

class Email
{
    public function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email: {$value}");
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

- [ ] **Step 4: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/ValueObjects/EmailTest.php
```

- [ ] **Step 5: Write failing test for StatusTurma**

```php
<?php
// tests/Entities/StatusTurmaTest.php
namespace Tests\Entities;

use App\Entities\StatusTurma;
use PHPUnit\Framework\TestCase;

class StatusTurmaTest extends TestCase
{
    public function test_from_string_disponivel(): void
    {
        $this->assertSame(StatusTurma::Disponivel, StatusTurma::fromString('disponivel'));
    }

    public function test_from_string_encerrado(): void
    {
        $this->assertSame(StatusTurma::Encerrado, StatusTurma::fromString('encerrado'));
    }

    public function test_from_string_invalid_throws(): void
    {
        $this->expectException(\ValueError::class);
        StatusTurma::fromString('outro');
    }

    public function test_to_string_disponivel(): void
    {
        $this->assertSame('disponivel', StatusTurma::Disponivel->toString());
    }

    public function test_to_string_encerrado(): void
    {
        $this->assertSame('encerrado', StatusTurma::Encerrado->toString());
    }
}
```

- [ ] **Step 6: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Entities/StatusTurmaTest.php
```
Expected: `Error: Call to undefined method App\Entities\StatusTurma::fromString()`.

- [ ] **Step 7: Implement StatusTurma serialization**

```php
<?php
// app/Entities/StatusTurma.php
namespace App\Entities;

enum StatusTurma
{
    case Disponivel;
    case Encerrado;

    public static function fromString(string $value): self
    {
        return match(strtolower($value)) {
            'disponivel' => self::Disponivel,
            'encerrado'  => self::Encerrado,
            default      => throw new \ValueError("Invalid status: {$value}"),
        };
    }

    public function toString(): string
    {
        return match($this) {
            self::Disponivel => 'disponivel',
            self::Encerrado  => 'encerrado',
        };
    }
}
```

- [ ] **Step 8: Write failing test for Temas**

```php
<?php
// tests/Entities/TemasTest.php
namespace Tests\Entities;

use App\Entities\Temas;
use PHPUnit\Framework\TestCase;

class TemasTest extends TestCase
{
    public function test_from_string_all_cases(): void
    {
        $this->assertSame(Temas::Inovacao, Temas::fromString('inovacao'));
        $this->assertSame(Temas::Tecnologia, Temas::fromString('tecnologia'));
        $this->assertSame(Temas::Marketing, Temas::fromString('marketing'));
        $this->assertSame(Temas::Empreendedorismo, Temas::fromString('empreendedorismo'));
        $this->assertSame(Temas::Agro, Temas::fromString('agro'));
    }

    public function test_from_string_invalid_throws(): void
    {
        $this->expectException(\ValueError::class);
        Temas::fromString('outro');
    }

    public function test_to_string_roundtrip(): void
    {
        foreach (Temas::cases() as $tema) {
            $this->assertSame($tema, Temas::fromString($tema->toString()));
        }
    }
}
```

- [ ] **Step 9: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Entities/TemasTest.php
```

- [ ] **Step 10: Implement Temas serialization**

```php
<?php
// app/Entities/Temas.php
namespace App\Entities;

enum Temas
{
    case Inovacao;
    case Tecnologia;
    case Marketing;
    case Empreendedorismo;
    case Agro;

    public static function fromString(string $value): self
    {
        return match(strtolower($value)) {
            'inovacao'         => self::Inovacao,
            'tecnologia'       => self::Tecnologia,
            'marketing'        => self::Marketing,
            'empreendedorismo' => self::Empreendedorismo,
            'agro'             => self::Agro,
            default            => throw new \ValueError("Invalid tema: {$value}"),
        };
    }

    public function toString(): string
    {
        return match($this) {
            self::Inovacao         => 'inovacao',
            self::Tecnologia       => 'tecnologia',
            self::Marketing        => 'marketing',
            self::Empreendedorismo => 'empreendedorismo',
            self::Agro             => 'agro',
        };
    }
}
```

- [ ] **Step 11: Run all three — expect PASS**

```bash
./vendor/bin/phpunit tests/ValueObjects/EmailTest.php tests/Entities/StatusTurmaTest.php tests/Entities/TemasTest.php
```

- [ ] **Step 12: Commit**

```bash
git add app/ValueObjects/Email.php app/Entities/StatusTurma.php app/Entities/Temas.php \
        tests/ValueObjects/EmailTest.php tests/Entities/StatusTurmaTest.php tests/Entities/TemasTest.php
git commit -m "feat: add Email VO constructor and enum serialization methods"
```

---

## Task 2: Update Entities — Add IDs, Getters, Turma.cursoId

**Files:**
- Modify: `app/Entities/Curso.php`
- Modify: `app/Entities/Turma.php`
- Modify: `app/Entities/Usuario.php`
- Test: `tests/Entities/CursoTest.php`
- Test: `tests/Entities/TurmaTest.php`
- Test: `tests/Entities/UsuarioTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Entities/CursoTest.php
namespace Tests\Entities;

use App\Entities\Curso;
use App\Entities\Temas;
use PHPUnit\Framework\TestCase;

class CursoTest extends TestCase
{
    private function make(): Curso
    {
        return new Curso('PHP Avançado', 'Aprenda PHP', Temas::Tecnologia, 'https://img.jpg');
    }

    public function test_id_is_null_by_default(): void
    {
        $this->assertNull($this->make()->getId());
    }

    public function test_set_and_get_id(): void
    {
        $curso = $this->make();
        $curso->setId(42);
        $this->assertSame(42, $curso->getId());
    }

    public function test_getters_return_constructor_values(): void
    {
        $curso = $this->make();
        $this->assertSame('PHP Avançado', $curso->getTitulo());
        $this->assertSame('Aprenda PHP', $curso->getDescricao());
        $this->assertSame(Temas::Tecnologia, $curso->getTema());
        $this->assertSame('https://img.jpg', $curso->getUrlImagem());
    }
}
```

```php
<?php
// tests/Entities/TurmaTest.php
namespace Tests\Entities;

use App\Entities\StatusTurma;
use App\Entities\Turma;
use DateTime;
use PHPUnit\Framework\TestCase;

class TurmaTest extends TestCase
{
    private function make(): Turma
    {
        return new Turma(
            'Turma A',
            'Primeira turma',
            30,
            StatusTurma::Disponivel,
            new DateTime('2026-06-01'),
            new DateTime('2026-12-01'),
            5
        );
    }

    public function test_id_is_null_by_default(): void
    {
        $this->assertNull($this->make()->getId());
    }

    public function test_set_and_get_id(): void
    {
        $turma = $this->make();
        $turma->setId(7);
        $this->assertSame(7, $turma->getId());
    }

    public function test_curso_id_accessible(): void
    {
        $this->assertSame(5, $this->make()->getCursoId());
    }

    public function test_getters_return_constructor_values(): void
    {
        $turma = $this->make();
        $this->assertSame('Turma A', $turma->getTitulo());
        $this->assertSame('Primeira turma', $turma->getDescricao());
        $this->assertSame(30, $turma->getQuantidadeVagas());
        $this->assertSame(StatusTurma::Disponivel, $turma->getStatus());
        $this->assertSame(5, $turma->getCursoId());
    }
}
```

```php
<?php
// tests/Entities/UsuarioTest.php
namespace Tests\Entities;

use App\Entities\Usuario;
use App\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

class UsuarioTest extends TestCase
{
    private function make(): Usuario
    {
        return new Usuario('João Silva', new Email('joao@example.com'));
    }

    public function test_id_is_null_by_default(): void
    {
        $this->assertNull($this->make()->getId());
    }

    public function test_set_and_get_id(): void
    {
        $usuario = $this->make();
        $usuario->setId(3);
        $this->assertSame(3, $usuario->getId());
    }

    public function test_getters_return_constructor_values(): void
    {
        $usuario = $this->make();
        $this->assertSame('João Silva', $usuario->getNome());
        $this->assertSame('joao@example.com', $usuario->getEmail()->getValue());
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Entities/CursoTest.php tests/Entities/TurmaTest.php tests/Entities/UsuarioTest.php
```

- [ ] **Step 3: Update Curso**

```php
<?php
// app/Entities/Curso.php
namespace App\Entities;

class Curso
{
    public function __construct(
        private string $titulo,
        private string $descrição,
        private Temas $tema,
        private string $urlImagem,
        private ?int $id = null
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTitulo(): string { return $this->titulo; }
    public function getDescricao(): string { return $this->descrição; }
    public function getTema(): Temas { return $this->tema; }
    public function getUrlImagem(): string { return $this->urlImagem; }
    public function setId(int $id): void { $this->id = $id; }
}
```

- [ ] **Step 4: Update Turma**

```php
<?php
// app/Entities/Turma.php
namespace App\Entities;

use DateTime;

class Turma
{
    public function __construct(
        private string $titulo,
        private string $descrição,
        private int $quantidadeVagas,
        private StatusTurma $status,
        private DateTime $dataInicio,
        private DateTime $dataFim,
        private int $cursoId,
        private ?int $id = null
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTitulo(): string { return $this->titulo; }
    public function getDescricao(): string { return $this->descrição; }
    public function getQuantidadeVagas(): int { return $this->quantidadeVagas; }
    public function getStatus(): StatusTurma { return $this->status; }
    public function getDataInicio(): DateTime { return $this->dataInicio; }
    public function getDataFim(): DateTime { return $this->dataFim; }
    public function getCursoId(): int { return $this->cursoId; }
    public function setId(int $id): void { $this->id = $id; }
}
```

- [ ] **Step 5: Update Usuario**

```php
<?php
// app/Entities/Usuario.php
namespace App\Entities;

use App\ValueObjects\Email;

class Usuario
{
    public function __construct(
        private string $nome,
        private Email $email,
        private ?int $id = null
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getEmail(): Email { return $this->email; }
    public function setId(int $id): void { $this->id = $id; }
}
```

- [ ] **Step 6: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/Entities/CursoTest.php tests/Entities/TurmaTest.php tests/Entities/UsuarioTest.php
```

- [ ] **Step 7: Commit**

```bash
git add app/Entities/Curso.php app/Entities/Turma.php app/Entities/Usuario.php \
        tests/Entities/CursoTest.php tests/Entities/TurmaTest.php tests/Entities/UsuarioTest.php
git commit -m "feat: add IDs, getters, and cursoId to domain entities"
```

---

## Task 3: Matricula Entity + HTTP Exceptions

**Files:**
- Create: `app/Entities/Matricula.php`
- Create: `app/Exceptions/Http/NotFound.php`
- Create: `app/Exceptions/Http/UnprocessableEntity.php`
- Test: `tests/Entities/MatriculaTest.php`
- Test: `tests/Exceptions/Http/UnprocessableEntityTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Entities/MatriculaTest.php
namespace Tests\Entities;

use App\Entities\Matricula;
use PHPUnit\Framework\TestCase;

class MatriculaTest extends TestCase
{
    public function test_id_is_null_by_default(): void
    {
        $m = new Matricula(1, 2, 3);
        $this->assertNull($m->getId());
    }

    public function test_set_and_get_id(): void
    {
        $m = new Matricula(1, 2, 3);
        $m->setId(10);
        $this->assertSame(10, $m->getId());
    }

    public function test_getters(): void
    {
        $m = new Matricula(usuarioId: 1, turmaId: 2, cursoId: 3);
        $this->assertSame(1, $m->getUsuarioId());
        $this->assertSame(2, $m->getTurmaId());
        $this->assertSame(3, $m->getCursoId());
    }
}
```

```php
<?php
// tests/Exceptions/Http/UnprocessableEntityTest.php
namespace Tests\Exceptions\Http;

use App\Exceptions\Http\NotFound;
use App\Exceptions\Http\UnprocessableEntity;
use PHPUnit\Framework\TestCase;

class UnprocessableEntityTest extends TestCase
{
    public function test_not_found_status_code(): void
    {
        $e = new NotFound('Recurso não encontrado');
        $this->assertSame(404, $e->getStatusCode());
        $this->assertSame('Recurso não encontrado', $e->getMessage());
    }

    public function test_unprocessable_entity_status_code(): void
    {
        $e = new UnprocessableEntity('Dados inválidos');
        $this->assertSame(422, $e->getStatusCode());
        $this->assertSame('Dados inválidos', $e->getMessage());
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Entities/MatriculaTest.php tests/Exceptions/Http/UnprocessableEntityTest.php
```

- [ ] **Step 3: Create Matricula**

```php
<?php
// app/Entities/Matricula.php
namespace App\Entities;

class Matricula
{
    public function __construct(
        private int $usuarioId,
        private int $turmaId,
        private int $cursoId,
        private ?int $id = null
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getUsuarioId(): int { return $this->usuarioId; }
    public function getTurmaId(): int { return $this->turmaId; }
    public function getCursoId(): int { return $this->cursoId; }
    public function setId(int $id): void { $this->id = $id; }
}
```

- [ ] **Step 4: Create NotFound**

```php
<?php
// app/Exceptions/Http/NotFound.php
namespace App\Exceptions\Http;

use App\Contracts\HttpExceptionInterface;

class NotFound extends \RuntimeException implements HttpExceptionInterface
{
    public function __construct(string $message = 'Not Found')
    {
        parent::__construct($message, 404);
    }

    public function getStatusCode(): int
    {
        return 404;
    }
}
```

- [ ] **Step 5: Create UnprocessableEntity**

```php
<?php
// app/Exceptions/Http/UnprocessableEntity.php
namespace App\Exceptions\Http;

use App\Contracts\HttpExceptionInterface;

class UnprocessableEntity extends \RuntimeException implements HttpExceptionInterface
{
    public function __construct(string $message = 'Unprocessable Entity')
    {
        parent::__construct($message, 422);
    }

    public function getStatusCode(): int
    {
        return 422;
    }
}
```

- [ ] **Step 6: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/Entities/MatriculaTest.php tests/Exceptions/Http/UnprocessableEntityTest.php
```

- [ ] **Step 7: Commit**

```bash
git add app/Entities/Matricula.php app/Exceptions/Http/NotFound.php \
        app/Exceptions/Http/UnprocessableEntity.php \
        tests/Entities/MatriculaTest.php tests/Exceptions/Http/UnprocessableEntityTest.php
git commit -m "feat: add Matricula entity and HTTP 404/422 exceptions"
```

---

## Task 4: Update Contracts + Add MatriculaContracts + Container::setContainer

**Files:**
- Modify: `app/Contracts/Repositories/CursoRepositoryInterface.php`
- Modify: `app/Contracts/Repositories/TurmaRepositoryInterface.php`
- Create: `app/Contracts/Repositories/MatriculaRepositoryInterface.php`
- Create: `app/Contracts/Services/MatriculaServiceInterface.php`
- Modify: `app/Support/Container.php`

These are interfaces and a utility method — no behaviour to test beyond compilation. One smoke test covers the new Container method.

- [ ] **Step 1: Write failing test for Container::setContainer**

```php
<?php
// tests/Support/ContainerSetContainerTest.php
namespace Tests\Support;

use App\Support\Container;
use PHPUnit\Framework\TestCase;

class ContainerSetContainerTest extends TestCase
{
    public function test_set_container_replaces_instance(): void
    {
        $original = Container::getContainer();
        $fresh = new Container();
        Container::setContainer($fresh);
        $this->assertSame($fresh, Container::getContainer());

        // restore original so other tests are unaffected
        Container::setContainer($original);
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Support/ContainerSetContainerTest.php
```
Expected: `Error: Call to undefined method App\Support\Container::setContainer()`.

- [ ] **Step 3: Add setContainer to Container**

Open `app/Support/Container.php`. Find the `getContainer()` static method and add the new static method immediately after it:

```php
public static function setContainer(Container $instance): void
{
    static::$instance = $instance;
}
```

- [ ] **Step 4: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/Support/ContainerSetContainerTest.php
```

- [ ] **Step 5: Update CursoRepositoryInterface**

```php
<?php
// app/Contracts/Repositories/CursoRepositoryInterface.php
namespace App\Contracts\Repositories;

use App\Entities\Curso;

interface CursoRepositoryInterface
{
    public function get(array $filters = []): array;
    public function find(int $id): Curso;
    public function store(mixed $data): Curso;
    public function update(int $id, mixed $data): Curso;
    public function destroy(int $id): void;
    public function getWithAvailableTurmas(array $filters = []): array;
}
```

- [ ] **Step 6: Update TurmaRepositoryInterface**

```php
<?php
// app/Contracts/Repositories/TurmaRepositoryInterface.php
namespace App\Contracts\Repositories;

use App\Entities\Turma;

interface TurmaRepositoryInterface
{
    public function get(array $filters = []): array;
    public function find(int $id): Turma;
    public function store(mixed $data): Turma;
    public function update(int $id, mixed $data): Turma;
    public function destroy(int $id): void;
    public function findByCursoId(int $cursoId): array;
}
```

- [ ] **Step 7: Create MatriculaRepositoryInterface**

```php
<?php
// app/Contracts/Repositories/MatriculaRepositoryInterface.php
namespace App\Contracts\Repositories;

use App\Entities\Matricula;

interface MatriculaRepositoryInterface
{
    public function store(Matricula $matricula): Matricula;
    public function findByUsuarioAndCurso(int $usuarioId, int $cursoId): ?Matricula;
    public function getByUsuario(int $usuarioId): array;
    public function destroy(int $id): void;
}
```

- [ ] **Step 8: Create MatriculaServiceInterface**

```php
<?php
// app/Contracts/Services/MatriculaServiceInterface.php
namespace App\Contracts\Services;

use App\Entities\Matricula;

interface MatriculaServiceInterface
{
    public function enroll(int $usuarioId, int $turmaId): Matricula;
    public function getByUsuario(int $usuarioId): array;
}
```

- [ ] **Step 9: Commit**

```bash
git add app/Contracts/Repositories/CursoRepositoryInterface.php \
        app/Contracts/Repositories/TurmaRepositoryInterface.php \
        app/Contracts/Repositories/MatriculaRepositoryInterface.php \
        app/Contracts/Services/MatriculaServiceInterface.php \
        app/Support/Container.php \
        tests/Support/ContainerSetContainerTest.php
git commit -m "feat: update contracts, add Matricula contracts, Container::setContainer"
```

---

## Task 5: BaseRepository + CursoRepository

**Files:**
- Create: `app/Repositories/BaseRepository.php`
- Create: `app/Repositories/CursoRepository.php`
- Test: `tests/Repositories/CursoRepositoryTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Repositories/CursoRepositoryTest.php
namespace Tests\Repositories;

use App\Entities\Curso;
use App\Entities\Temas;
use App\Repositories\CursoRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class CursoRepositoryTest extends TestCase
{
    private PDO $pdo;
    private CursoRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE cursos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            descricao TEXT NOT NULL,
            tema TEXT NOT NULL,
            url_imagem TEXT NOT NULL
        )');
        $this->pdo->exec('CREATE TABLE turmas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            curso_id INTEGER NOT NULL,
            titulo TEXT NOT NULL,
            descricao TEXT NOT NULL,
            quantidade_vagas INTEGER NOT NULL,
            status TEXT NOT NULL,
            data_inicio TEXT NOT NULL,
            data_fim TEXT NOT NULL
        )');
        $this->repo = new CursoRepository($this->pdo);
    }

    public function test_store_returns_curso_with_id(): void
    {
        $curso = $this->repo->store([
            'titulo'     => 'PHP Avançado',
            'descricao'  => 'Aprenda PHP',
            'tema'       => 'tecnologia',
            'url_imagem' => 'https://img.jpg',
        ]);
        $this->assertInstanceOf(Curso::class, $curso);
        $this->assertNotNull($curso->getId());
        $this->assertSame('PHP Avançado', $curso->getTitulo());
        $this->assertSame(Temas::Tecnologia, $curso->getTema());
    }

    public function test_find_returns_stored_curso(): void
    {
        $stored = $this->repo->store([
            'titulo' => 'Marketing Digital', 'descricao' => 'desc',
            'tema' => 'marketing', 'url_imagem' => 'img.jpg',
        ]);
        $found = $this->repo->find($stored->getId());
        $this->assertSame($stored->getId(), $found->getId());
        $this->assertSame('Marketing Digital', $found->getTitulo());
    }

    public function test_find_throws_for_unknown_id(): void
    {
        $this->expectException(\App\Exceptions\Http\NotFound::class);
        $this->repo->find(999);
    }

    public function test_update_changes_fields(): void
    {
        $curso = $this->repo->store([
            'titulo' => 'Antigo', 'descricao' => 'desc',
            'tema' => 'agro', 'url_imagem' => 'img.jpg',
        ]);
        $updated = $this->repo->update($curso->getId(), [
            'titulo' => 'Novo', 'descricao' => 'nova desc',
            'tema' => 'inovacao', 'url_imagem' => 'new.jpg',
        ]);
        $this->assertSame('Novo', $updated->getTitulo());
        $this->assertSame(Temas::Inovacao, $updated->getTema());
    }

    public function test_destroy_removes_record(): void
    {
        $curso = $this->repo->store([
            'titulo' => 'Delete Me', 'descricao' => 'desc',
            'tema' => 'agro', 'url_imagem' => 'img.jpg',
        ]);
        $this->repo->destroy($curso->getId());
        $this->expectException(\App\Exceptions\Http\NotFound::class);
        $this->repo->find($curso->getId());
    }

    public function test_get_with_available_turmas_returns_matching_cursos(): void
    {
        $curso = $this->repo->store([
            'titulo' => 'Agro Futuro', 'descricao' => 'desc',
            'tema' => 'agro', 'url_imagem' => 'img.jpg',
        ]);
        // Insert an available turma with dates covering today
        $today = date('Y-m-d');
        $past  = date('Y-m-d', strtotime('-10 days'));
        $future = date('Y-m-d', strtotime('+10 days'));
        $this->pdo->exec("INSERT INTO turmas VALUES (
            NULL, {$curso->getId()}, 'T1', 'desc', 10, 'disponivel', '{$past}', '{$future}'
        )");
        $results = $this->repo->getWithAvailableTurmas([]);
        $this->assertCount(1, $results);
        $this->assertSame($curso->getId(), $results[0]->getId());
    }

    public function test_get_with_available_turmas_filters_by_titulo(): void
    {
        $c1 = $this->repo->store(['titulo' => 'PHP Dev', 'descricao' => 'd', 'tema' => 'tecnologia', 'url_imagem' => 'i.jpg']);
        $c2 = $this->repo->store(['titulo' => 'Marketing Pro', 'descricao' => 'd', 'tema' => 'marketing', 'url_imagem' => 'i.jpg']);
        $past = date('Y-m-d', strtotime('-10 days'));
        $future = date('Y-m-d', strtotime('+10 days'));
        $this->pdo->exec("INSERT INTO turmas VALUES (NULL, {$c1->getId()}, 'T', 'd', 10, 'disponivel', '{$past}', '{$future}')");
        $this->pdo->exec("INSERT INTO turmas VALUES (NULL, {$c2->getId()}, 'T', 'd', 10, 'disponivel', '{$past}', '{$future}')");

        $results = $this->repo->getWithAvailableTurmas(['titulo' => 'PHP']);
        $this->assertCount(1, $results);
        $this->assertSame('PHP Dev', $results[0]->getTitulo());
    }

    public function test_get_with_available_turmas_filters_by_tema(): void
    {
        $c1 = $this->repo->store(['titulo' => 'A', 'descricao' => 'd', 'tema' => 'tecnologia', 'url_imagem' => 'i.jpg']);
        $c2 = $this->repo->store(['titulo' => 'B', 'descricao' => 'd', 'tema' => 'agro', 'url_imagem' => 'i.jpg']);
        $past = date('Y-m-d', strtotime('-10 days'));
        $future = date('Y-m-d', strtotime('+10 days'));
        $this->pdo->exec("INSERT INTO turmas VALUES (NULL, {$c1->getId()}, 'T', 'd', 10, 'disponivel', '{$past}', '{$future}')");
        $this->pdo->exec("INSERT INTO turmas VALUES (NULL, {$c2->getId()}, 'T', 'd', 10, 'disponivel', '{$past}', '{$future}')");

        $results = $this->repo->getWithAvailableTurmas(['tema' => 'agro']);
        $this->assertCount(1, $results);
        $this->assertSame('B', $results[0]->getTitulo());
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Repositories/CursoRepositoryTest.php
```

- [ ] **Step 3: Create BaseRepository**

```php
<?php
// app/Repositories/BaseRepository.php
namespace App\Repositories;

abstract class BaseRepository
{
    public function __construct(protected \PDO $pdo) {}
}
```

- [ ] **Step 4: Create CursoRepository**

```php
<?php
// app/Repositories/CursoRepository.php
namespace App\Repositories;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Entities\Curso;
use App\Entities\Temas;
use App\Exceptions\Http\NotFound;

class CursoRepository extends BaseRepository implements CursoRepositoryInterface
{
    public function get(array $filters = []): array
    {
        $sql = 'SELECT * FROM cursos';
        $params = [];
        $conditions = [];

        if (!empty($filters['titulo'])) {
            $conditions[] = 'titulo LIKE ?';
            $params[] = '%' . $filters['titulo'] . '%';
        }
        if (!empty($filters['tema'])) {
            $conditions[] = 'tema = ?';
            $params[] = $filters['tema'];
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function find(int $id): Curso
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cursos WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            throw new NotFound("Curso {$id} não encontrado");
        }
        return $this->hydrate($row);
    }

    public function store(mixed $data): Curso
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cursos (titulo, descricao, tema, url_imagem) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['titulo'],
            $data['descricao'],
            $data['tema'],
            $data['url_imagem'],
        ]);
        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, mixed $data): Curso
    {
        $this->find($id);
        $stmt = $this->pdo->prepare(
            'UPDATE cursos SET titulo = ?, descricao = ?, tema = ?, url_imagem = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['titulo'],
            $data['descricao'],
            $data['tema'],
            $data['url_imagem'],
            $id,
        ]);
        return $this->find($id);
    }

    public function destroy(int $id): void
    {
        $this->find($id);
        $stmt = $this->pdo->prepare('DELETE FROM cursos WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function getWithAvailableTurmas(array $filters = []): array
    {
        $today = (new \DateTime())->format('Y-m-d');
        $sql = 'SELECT DISTINCT cursos.id, cursos.titulo, cursos.descricao, cursos.tema, cursos.url_imagem
                FROM cursos
                JOIN turmas ON turmas.curso_id = cursos.id
                WHERE turmas.status = ?
                AND ? BETWEEN turmas.data_inicio AND turmas.data_fim';
        $params = ['disponivel', $today];

        if (!empty($filters['titulo'])) {
            $sql .= ' AND cursos.titulo LIKE ?';
            $params[] = '%' . $filters['titulo'] . '%';
        }
        if (!empty($filters['tema'])) {
            $sql .= ' AND cursos.tema = ?';
            $params[] = $filters['tema'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function hydrate(array $row): Curso
    {
        $curso = new Curso(
            $row['titulo'],
            $row['descricao'],
            Temas::fromString($row['tema']),
            $row['url_imagem']
        );
        $curso->setId((int) $row['id']);
        return $curso;
    }
}
```

- [ ] **Step 5: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/Repositories/CursoRepositoryTest.php
```

- [ ] **Step 6: Commit**

```bash
git add app/Repositories/BaseRepository.php app/Repositories/CursoRepository.php \
        tests/Repositories/CursoRepositoryTest.php
git commit -m "feat: add BaseRepository and CursoRepository with PDO"
```

---

## Task 6: TurmaRepository

**Files:**
- Create: `app/Repositories/TurmaRepository.php`
- Test: `tests/Repositories/TurmaRepositoryTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Repositories/TurmaRepositoryTest.php
namespace Tests\Repositories;

use App\Entities\StatusTurma;
use App\Entities\Turma;
use App\Exceptions\Http\NotFound;
use App\Repositories\TurmaRepository;
use DateTime;
use PDO;
use PHPUnit\Framework\TestCase;

class TurmaRepositoryTest extends TestCase
{
    private PDO $pdo;
    private TurmaRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE turmas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            curso_id INTEGER NOT NULL,
            titulo TEXT NOT NULL,
            descricao TEXT NOT NULL,
            quantidade_vagas INTEGER NOT NULL,
            status TEXT NOT NULL,
            data_inicio TEXT NOT NULL,
            data_fim TEXT NOT NULL
        )');
        $this->repo = new TurmaRepository($this->pdo);
    }

    private function data(int $cursoId = 1): array
    {
        return [
            'curso_id'          => $cursoId,
            'titulo'            => 'Turma A',
            'descricao'         => 'Desc',
            'quantidade_vagas'  => 30,
            'status'            => 'disponivel',
            'data_inicio'       => '2026-06-01',
            'data_fim'          => '2026-12-01',
        ];
    }

    public function test_store_returns_turma_with_id(): void
    {
        $turma = $this->repo->store($this->data());
        $this->assertInstanceOf(Turma::class, $turma);
        $this->assertNotNull($turma->getId());
        $this->assertSame('Turma A', $turma->getTitulo());
        $this->assertSame(StatusTurma::Disponivel, $turma->getStatus());
    }

    public function test_find_returns_stored_turma(): void
    {
        $stored = $this->repo->store($this->data());
        $found  = $this->repo->find($stored->getId());
        $this->assertSame($stored->getId(), $found->getId());
    }

    public function test_find_throws_for_unknown_id(): void
    {
        $this->expectException(NotFound::class);
        $this->repo->find(999);
    }

    public function test_update_changes_status(): void
    {
        $turma   = $this->repo->store($this->data());
        $updated = $this->repo->update($turma->getId(), array_merge($this->data(), ['status' => 'encerrado']));
        $this->assertSame(StatusTurma::Encerrado, $updated->getStatus());
    }

    public function test_destroy_removes_record(): void
    {
        $turma = $this->repo->store($this->data());
        $this->repo->destroy($turma->getId());
        $this->expectException(NotFound::class);
        $this->repo->find($turma->getId());
    }

    public function test_find_by_curso_id_returns_matching_turmas(): void
    {
        $this->repo->store($this->data(1));
        $this->repo->store($this->data(1));
        $this->repo->store($this->data(2));
        $results = $this->repo->findByCursoId(1);
        $this->assertCount(2, $results);
        foreach ($results as $t) {
            $this->assertSame(1, $t->getCursoId());
        }
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Repositories/TurmaRepositoryTest.php
```

- [ ] **Step 3: Create TurmaRepository**

```php
<?php
// app/Repositories/TurmaRepository.php
namespace App\Repositories;

use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Entities\StatusTurma;
use App\Entities\Turma;
use App\Exceptions\Http\NotFound;
use DateTime;

class TurmaRepository extends BaseRepository implements TurmaRepositoryInterface
{
    public function get(array $filters = []): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM turmas');
        $stmt->execute();
        return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function find(int $id): Turma
    {
        $stmt = $this->pdo->prepare('SELECT * FROM turmas WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            throw new NotFound("Turma {$id} não encontrada");
        }
        return $this->hydrate($row);
    }

    public function store(mixed $data): Turma
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO turmas (curso_id, titulo, descricao, quantidade_vagas, status, data_inicio, data_fim)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['curso_id'],
            $data['titulo'],
            $data['descricao'],
            $data['quantidade_vagas'],
            $data['status'],
            $data['data_inicio'],
            $data['data_fim'],
        ]);
        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, mixed $data): Turma
    {
        $this->find($id);
        $stmt = $this->pdo->prepare(
            'UPDATE turmas SET curso_id = ?, titulo = ?, descricao = ?, quantidade_vagas = ?,
             status = ?, data_inicio = ?, data_fim = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['curso_id'],
            $data['titulo'],
            $data['descricao'],
            $data['quantidade_vagas'],
            $data['status'],
            $data['data_inicio'],
            $data['data_fim'],
            $id,
        ]);
        return $this->find($id);
    }

    public function destroy(int $id): void
    {
        $this->find($id);
        $stmt = $this->pdo->prepare('DELETE FROM turmas WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function findByCursoId(int $cursoId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM turmas WHERE curso_id = ?');
        $stmt->execute([$cursoId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function hydrate(array $row): Turma
    {
        $turma = new Turma(
            $row['titulo'],
            $row['descricao'],
            (int) $row['quantidade_vagas'],
            StatusTurma::fromString($row['status']),
            new DateTime($row['data_inicio']),
            new DateTime($row['data_fim']),
            (int) $row['curso_id']
        );
        $turma->setId((int) $row['id']);
        return $turma;
    }
}
```

- [ ] **Step 4: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/Repositories/TurmaRepositoryTest.php
```

- [ ] **Step 5: Commit**

```bash
git add app/Repositories/TurmaRepository.php tests/Repositories/TurmaRepositoryTest.php
git commit -m "feat: add TurmaRepository with PDO"
```

---

## Task 7: UsuarioRepository

**Files:**
- Create: `app/Repositories/UsuarioRepository.php`
- Test: `tests/Repositories/UsuarioRepositoryTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Repositories/UsuarioRepositoryTest.php
namespace Tests\Repositories;

use App\Entities\Usuario;
use App\Exceptions\Http\NotFound;
use App\Repositories\UsuarioRepository;
use App\ValueObjects\Email;
use PDO;
use PHPUnit\Framework\TestCase;

class UsuarioRepositoryTest extends TestCase
{
    private PDO $pdo;
    private UsuarioRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE
        )');
        $this->repo = new UsuarioRepository($this->pdo);
    }

    public function test_store_returns_usuario_with_id(): void
    {
        $u = $this->repo->store(['nome' => 'João', 'email' => 'joao@test.com']);
        $this->assertInstanceOf(Usuario::class, $u);
        $this->assertNotNull($u->getId());
        $this->assertSame('João', $u->getNome());
        $this->assertSame('joao@test.com', $u->getEmail()->getValue());
    }

    public function test_find_throws_for_unknown_id(): void
    {
        $this->expectException(NotFound::class);
        $this->repo->find(999);
    }

    public function test_destroy_removes_record(): void
    {
        $u = $this->repo->store(['nome' => 'Ana', 'email' => 'ana@test.com']);
        $this->repo->destroy($u->getId());
        $this->expectException(NotFound::class);
        $this->repo->find($u->getId());
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Repositories/UsuarioRepositoryTest.php
```

- [ ] **Step 3: Create UsuarioRepository**

```php
<?php
// app/Repositories/UsuarioRepository.php
namespace App\Repositories;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Entities\Usuario;
use App\Exceptions\Http\NotFound;
use App\ValueObjects\Email;

class UsuarioRepository extends BaseRepository implements UsuarioRepositoryInterface
{
    public function get(array $filters = []): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios');
        $stmt->execute();
        return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function find(int $id): Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            throw new NotFound("Usuário {$id} não encontrado");
        }
        return $this->hydrate($row);
    }

    public function store(mixed $data): Usuario
    {
        $stmt = $this->pdo->prepare('INSERT INTO usuarios (nome, email) VALUES (?, ?)');
        $stmt->execute([$data['nome'], $data['email']]);
        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, mixed $data): Usuario
    {
        $this->find($id);
        $stmt = $this->pdo->prepare('UPDATE usuarios SET nome = ?, email = ? WHERE id = ?');
        $stmt->execute([$data['nome'], $data['email'], $id]);
        return $this->find($id);
    }

    public function destroy(int $id): void
    {
        $this->find($id);
        $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function hydrate(array $row): Usuario
    {
        $usuario = new Usuario($row['nome'], new Email($row['email']));
        $usuario->setId((int) $row['id']);
        return $usuario;
    }
}
```

- [ ] **Step 4: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/Repositories/UsuarioRepositoryTest.php
```

- [ ] **Step 5: Commit**

```bash
git add app/Repositories/UsuarioRepository.php tests/Repositories/UsuarioRepositoryTest.php
git commit -m "feat: add UsuarioRepository with PDO"
```

---

## Task 8: MatriculaRepository

**Files:**
- Create: `app/Repositories/MatriculaRepository.php`
- Test: `tests/Repositories/MatriculaRepositoryTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Repositories/MatriculaRepositoryTest.php
namespace Tests\Repositories;

use App\Entities\Matricula;
use App\Repositories\MatriculaRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class MatriculaRepositoryTest extends TestCase
{
    private PDO $pdo;
    private MatriculaRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE cursos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            descricao TEXT NOT NULL,
            tema TEXT NOT NULL,
            url_imagem TEXT NOT NULL
        )');
        $this->pdo->exec('CREATE TABLE matriculas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            turma_id INTEGER NOT NULL,
            curso_id INTEGER NOT NULL,
            UNIQUE(usuario_id, curso_id)
        )');
        $this->pdo->exec("INSERT INTO cursos VALUES (1, 'PHP', 'desc', 'tecnologia', 'img.jpg')");
        $this->repo = new MatriculaRepository($this->pdo);
    }

    public function test_store_returns_matricula_with_id(): void
    {
        $m = $this->repo->store(new Matricula(1, 2, 1));
        $this->assertNotNull($m->getId());
        $this->assertSame(1, $m->getUsuarioId());
        $this->assertSame(2, $m->getTurmaId());
        $this->assertSame(1, $m->getCursoId());
    }

    public function test_find_by_usuario_and_curso_returns_matricula(): void
    {
        $this->repo->store(new Matricula(1, 2, 1));
        $found = $this->repo->findByUsuarioAndCurso(1, 1);
        $this->assertNotNull($found);
        $this->assertSame(1, $found->getUsuarioId());
    }

    public function test_find_by_usuario_and_curso_returns_null_when_not_enrolled(): void
    {
        $result = $this->repo->findByUsuarioAndCurso(1, 1);
        $this->assertNull($result);
    }

    public function test_get_by_usuario_returns_all_matriculas(): void
    {
        $this->pdo->exec("INSERT INTO cursos VALUES (2, 'Marketing', 'desc', 'marketing', 'img.jpg')");
        $this->repo->store(new Matricula(1, 2, 1));
        $this->repo->store(new Matricula(1, 3, 2));
        $results = $this->repo->getByUsuario(1);
        $this->assertCount(2, $results);
    }

    public function test_destroy_removes_matricula(): void
    {
        $m = $this->repo->store(new Matricula(1, 2, 1));
        $this->repo->destroy($m->getId());
        $this->assertNull($this->repo->findByUsuarioAndCurso(1, 1));
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Repositories/MatriculaRepositoryTest.php
```

- [ ] **Step 3: Create MatriculaRepository**

```php
<?php
// app/Repositories/MatriculaRepository.php
namespace App\Repositories;

use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Entities\Matricula;

class MatriculaRepository extends BaseRepository implements MatriculaRepositoryInterface
{
    public function store(Matricula $matricula): Matricula
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO matriculas (usuario_id, turma_id, curso_id) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $matricula->getUsuarioId(),
            $matricula->getTurmaId(),
            $matricula->getCursoId(),
        ]);
        $id = (int) $this->pdo->lastInsertId();
        $matricula->setId($id);
        return $matricula;
    }

    public function findByUsuarioAndCurso(int $usuarioId, int $cursoId): ?Matricula
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM matriculas WHERE usuario_id = ? AND curso_id = ?'
        );
        $stmt->execute([$usuarioId, $cursoId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return $this->hydrate($row);
    }

    public function getByUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM matriculas WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function destroy(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM matriculas WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function hydrate(array $row): Matricula
    {
        $m = new Matricula(
            (int) $row['usuario_id'],
            (int) $row['turma_id'],
            (int) $row['curso_id']
        );
        $m->setId((int) $row['id']);
        return $m;
    }
}
```

- [ ] **Step 4: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/Repositories/MatriculaRepositoryTest.php
```

- [ ] **Step 5: Commit**

```bash
git add app/Repositories/MatriculaRepository.php tests/Repositories/MatriculaRepositoryTest.php
git commit -m "feat: add MatriculaRepository with PDO"
```

---

## Task 9: CursoService + TurmaService

**Files:**
- Modify: `app/Services/CursoService.php`
- Modify: `app/Services/TurmaService.php`
- Test: `tests/Services/CursoServiceTest.php`
- Test: `tests/Services/TurmaServiceTest.php`

- [ ] **Step 1: Write failing tests for CursoService**

```php
<?php
// tests/Services/CursoServiceTest.php
namespace Tests\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Entities\Curso;
use App\Entities\Temas;
use App\Exceptions\Http\NotFound;
use App\Services\CursoService;
use PHPUnit\Framework\TestCase;

class CursoServiceTest extends TestCase
{
    private CursoRepositoryInterface $repo;
    private CursoService $service;

    protected function setUp(): void
    {
        $this->repo    = $this->createMock(CursoRepositoryInterface::class);
        $this->service = new CursoService($this->repo);
    }

    private function makeCurso(int $id = 1): Curso
    {
        $c = new Curso('PHP', 'desc', Temas::Tecnologia, 'img.jpg');
        $c->setId($id);
        return $c;
    }

    public function test_get_available_delegates_to_repo(): void
    {
        $filters  = ['titulo' => 'PHP'];
        $expected = [$this->makeCurso()];
        $this->repo->expects($this->once())
            ->method('getWithAvailableTurmas')
            ->with($filters)
            ->willReturn($expected);

        $this->assertSame($expected, $this->service->getAvailable($filters));
    }

    public function test_find_delegates_to_repo(): void
    {
        $curso = $this->makeCurso();
        $this->repo->method('find')->with(1)->willReturn($curso);
        $this->assertSame($curso, $this->service->find(1));
    }

    public function test_find_propagates_not_found(): void
    {
        $this->repo->method('find')->willThrowException(new NotFound());
        $this->expectException(NotFound::class);
        $this->service->find(99);
    }

    public function test_store_delegates_to_repo(): void
    {
        $data  = ['titulo' => 'PHP', 'descricao' => 'd', 'tema' => 'tecnologia', 'url_imagem' => 'i.jpg'];
        $curso = $this->makeCurso();
        $this->repo->expects($this->once())->method('store')->with($data)->willReturn($curso);
        $this->assertSame($curso, $this->service->store($data));
    }

    public function test_update_delegates_to_repo(): void
    {
        $data    = ['titulo' => 'New', 'descricao' => 'd', 'tema' => 'agro', 'url_imagem' => 'i.jpg'];
        $updated = $this->makeCurso();
        $this->repo->expects($this->once())->method('update')->with(1, $data)->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, $data));
    }

    public function test_destroy_delegates_to_repo(): void
    {
        $this->repo->expects($this->once())->method('destroy')->with(1);
        $this->service->destroy(1);
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Services/CursoServiceTest.php
```

- [ ] **Step 3: Implement CursoService**

```php
<?php
// app/Services/CursoService.php
namespace App\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Services\CursoServiceInterface;
use App\Entities\Curso;

class CursoService implements CursoServiceInterface
{
    public function __construct(private CursoRepositoryInterface $repo) {}

    public function get(array $filters = []): array
    {
        return $this->repo->get($filters);
    }

    public function getAvailable(array $filters = []): array
    {
        return $this->repo->getWithAvailableTurmas($filters);
    }

    public function find(int $id): Curso
    {
        return $this->repo->find($id);
    }

    public function store(mixed $data): Curso
    {
        return $this->repo->store($data);
    }

    public function update(int $id, mixed $data): Curso
    {
        return $this->repo->update($id, $data);
    }

    public function destroy(int $id): void
    {
        $this->repo->destroy($id);
    }
}
```

- [ ] **Step 4: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/Services/CursoServiceTest.php
```

- [ ] **Step 5: Write failing tests for TurmaService**

```php
<?php
// tests/Services/TurmaServiceTest.php
namespace Tests\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Entities\Curso;
use App\Entities\StatusTurma;
use App\Entities\Temas;
use App\Entities\Turma;
use App\Exceptions\Http\NotFound;
use App\Services\TurmaService;
use DateTime;
use PHPUnit\Framework\TestCase;

class TurmaServiceTest extends TestCase
{
    private TurmaRepositoryInterface $turmaRepo;
    private CursoRepositoryInterface $cursoRepo;
    private TurmaService $service;

    protected function setUp(): void
    {
        $this->turmaRepo = $this->createMock(TurmaRepositoryInterface::class);
        $this->cursoRepo = $this->createMock(CursoRepositoryInterface::class);
        $this->service   = new TurmaService($this->turmaRepo, $this->cursoRepo);
    }

    private function makeTurma(int $id = 1, int $cursoId = 1): Turma
    {
        $t = new Turma('T', 'desc', 30, StatusTurma::Disponivel,
            new DateTime('2026-06-01'), new DateTime('2026-12-01'), $cursoId);
        $t->setId($id);
        return $t;
    }

    private function makeCurso(int $id = 1): Curso
    {
        $c = new Curso('PHP', 'desc', Temas::Tecnologia, 'img.jpg');
        $c->setId($id);
        return $c;
    }

    public function test_store_validates_curso_exists(): void
    {
        $this->cursoRepo->method('find')->with(1)->willReturn($this->makeCurso());
        $data  = ['titulo' => 'T', 'descricao' => 'd', 'quantidade_vagas' => 10,
                  'status' => 'disponivel', 'data_inicio' => '2026-06-01', 'data_fim' => '2026-12-01'];
        $turma = $this->makeTurma();
        $this->turmaRepo->expects($this->once())
            ->method('store')
            ->willReturn($turma);

        $result = $this->service->store(1, $data);
        $this->assertSame($turma, $result);
    }

    public function test_store_throws_when_curso_not_found(): void
    {
        $this->cursoRepo->method('find')->willThrowException(new NotFound());
        $this->expectException(NotFound::class);
        $this->service->store(99, []);
    }

    public function test_find_delegates_to_repo(): void
    {
        $turma = $this->makeTurma();
        $this->turmaRepo->method('find')->with(1)->willReturn($turma);
        $this->assertSame($turma, $this->service->find(1));
    }

    public function test_update_delegates_to_repo(): void
    {
        $data    = ['titulo' => 'T2', 'descricao' => 'd', 'quantidade_vagas' => 5,
                    'status' => 'encerrado', 'data_inicio' => '2026-06-01', 'data_fim' => '2026-12-01',
                    'curso_id' => 1];
        $updated = $this->makeTurma();
        $this->turmaRepo->expects($this->once())->method('update')->with(1, $data)->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, $data));
    }

    public function test_destroy_delegates_to_repo(): void
    {
        $this->turmaRepo->expects($this->once())->method('destroy')->with(1);
        $this->service->destroy(1);
    }
}
```

- [ ] **Step 6: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Services/TurmaServiceTest.php
```

- [ ] **Step 7: Implement TurmaService**

```php
<?php
// app/Services/TurmaService.php
namespace App\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Contracts\Services\TurmaServiceInterface;
use App\Entities\Turma;

class TurmaService implements TurmaServiceInterface
{
    public function __construct(
        private TurmaRepositoryInterface $turmaRepo,
        private CursoRepositoryInterface $cursoRepo
    ) {}

    public function get(array $filters = []): array
    {
        return $this->turmaRepo->get($filters);
    }

    public function find(int $id): Turma
    {
        return $this->turmaRepo->find($id);
    }

    public function store(int $cursoId, mixed $data): Turma
    {
        $this->cursoRepo->find($cursoId);
        $data['curso_id'] = $cursoId;
        return $this->turmaRepo->store($data);
    }

    public function update(int $id, mixed $data): Turma
    {
        return $this->turmaRepo->update($id, $data);
    }

    public function destroy(int $id): void
    {
        $this->turmaRepo->destroy($id);
    }
}
```

Note: `TurmaServiceInterface::store(mixed $data)` needs updating to `store(int $cursoId, mixed $data)` to match. Update the interface:

```php
// In app/Contracts/Services/TurmaServiceInterface.php
// Change this line:
public function store(mixed $data): Turma;
// To:
public function store(int $cursoId, mixed $data): Turma;
```

- [ ] **Step 8: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/Services/CursoServiceTest.php tests/Services/TurmaServiceTest.php
```

- [ ] **Step 9: Commit**

```bash
git add app/Services/CursoService.php app/Services/TurmaService.php \
        app/Contracts/Services/TurmaServiceInterface.php \
        tests/Services/CursoServiceTest.php tests/Services/TurmaServiceTest.php
git commit -m "feat: implement CursoService and TurmaService"
```

---

## Task 10: UsuarioService + MatriculaService

**Files:**
- Create: `app/Services/UsuarioService.php`
- Create: `app/Services/MatriculaService.php`
- Test: `tests/Services/UsuarioServiceTest.php`
- Test: `tests/Services/MatriculaServiceTest.php`

- [ ] **Step 1: Write failing tests for UsuarioService**

```php
<?php
// tests/Services/UsuarioServiceTest.php
namespace Tests\Services;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Entities\Usuario;
use App\Exceptions\Http\NotFound;
use App\Services\UsuarioService;
use App\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

class UsuarioServiceTest extends TestCase
{
    private UsuarioRepositoryInterface $repo;
    private UsuarioService $service;

    protected function setUp(): void
    {
        $this->repo    = $this->createMock(UsuarioRepositoryInterface::class);
        $this->service = new UsuarioService($this->repo);
    }

    private function makeUsuario(int $id = 1): Usuario
    {
        $u = new Usuario('João', new Email('joao@test.com'));
        $u->setId($id);
        return $u;
    }

    public function test_store_delegates_to_repo(): void
    {
        $data    = ['nome' => 'João', 'email' => 'joao@test.com'];
        $usuario = $this->makeUsuario();
        $this->repo->expects($this->once())->method('store')->with($data)->willReturn($usuario);
        $this->assertSame($usuario, $this->service->store($data));
    }

    public function test_find_delegates_to_repo(): void
    {
        $usuario = $this->makeUsuario();
        $this->repo->method('find')->with(1)->willReturn($usuario);
        $this->assertSame($usuario, $this->service->find(1));
    }

    public function test_find_propagates_not_found(): void
    {
        $this->repo->method('find')->willThrowException(new NotFound());
        $this->expectException(NotFound::class);
        $this->service->find(99);
    }

    public function test_destroy_delegates_to_repo(): void
    {
        $this->repo->expects($this->once())->method('destroy')->with(1);
        $this->service->destroy(1);
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Services/UsuarioServiceTest.php
```

- [ ] **Step 3: Create UsuarioService**

```php
<?php
// app/Services/UsuarioService.php
namespace App\Services;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Contracts\Services\UsuarioServiceInterface;
use App\Entities\Usuario;

class UsuarioService implements UsuarioServiceInterface
{
    public function __construct(private UsuarioRepositoryInterface $repo) {}

    public function get(array $filters = []): array
    {
        return $this->repo->get($filters);
    }

    public function find(int $id): Usuario
    {
        return $this->repo->find($id);
    }

    public function store(mixed $data): Usuario
    {
        return $this->repo->store($data);
    }

    public function update(int $id, mixed $data): Usuario
    {
        return $this->repo->update($id, $data);
    }

    public function destroy(int $id): void
    {
        $this->repo->destroy($id);
    }
}
```

- [ ] **Step 4: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/Services/UsuarioServiceTest.php
```

- [ ] **Step 5: Write failing tests for MatriculaService**

```php
<?php
// tests/Services/MatriculaServiceTest.php
namespace Tests\Services;

use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Entities\Matricula;
use App\Entities\StatusTurma;
use App\Entities\Turma;
use App\Exceptions\Http\NotFound;
use App\Exceptions\Http\UnprocessableEntity;
use App\Services\MatriculaService;
use DateTime;
use PHPUnit\Framework\TestCase;

class MatriculaServiceTest extends TestCase
{
    private MatriculaRepositoryInterface $matriculaRepo;
    private TurmaRepositoryInterface $turmaRepo;
    private MatriculaService $service;

    protected function setUp(): void
    {
        $this->matriculaRepo = $this->createMock(MatriculaRepositoryInterface::class);
        $this->turmaRepo     = $this->createMock(TurmaRepositoryInterface::class);
        $this->service       = new MatriculaService($this->matriculaRepo, $this->turmaRepo);
    }

    private function makeAvailableTurma(int $id = 1, int $cursoId = 1): Turma
    {
        $t = new Turma('T', 'desc', 30, StatusTurma::Disponivel,
            new DateTime('-1 day'), new DateTime('+1 day'), $cursoId);
        $t->setId($id);
        return $t;
    }

    public function test_enroll_succeeds_for_available_turma(): void
    {
        $turma = $this->makeAvailableTurma();
        $this->turmaRepo->method('find')->with(1)->willReturn($turma);
        $this->matriculaRepo->method('findByUsuarioAndCurso')->willReturn(null);

        $stored = new Matricula(1, 1, 1);
        $stored->setId(10);
        $this->matriculaRepo->expects($this->once())->method('store')->willReturn($stored);

        $result = $this->service->enroll(1, 1);
        $this->assertSame(10, $result->getId());
    }

    public function test_enroll_throws_when_turma_not_found(): void
    {
        $this->turmaRepo->method('find')->willThrowException(new NotFound());
        $this->expectException(NotFound::class);
        $this->service->enroll(1, 99);
    }

    public function test_enroll_throws_when_turma_encerrada(): void
    {
        $t = new Turma('T', 'desc', 10, StatusTurma::Encerrado,
            new DateTime('-1 day'), new DateTime('+1 day'), 1);
        $t->setId(1);
        $this->turmaRepo->method('find')->willReturn($t);

        $this->expectException(UnprocessableEntity::class);
        $this->service->enroll(1, 1);
    }

    public function test_enroll_throws_when_before_data_inicio(): void
    {
        $t = new Turma('T', 'desc', 10, StatusTurma::Disponivel,
            new DateTime('+5 days'), new DateTime('+30 days'), 1);
        $t->setId(1);
        $this->turmaRepo->method('find')->willReturn($t);

        $this->expectException(UnprocessableEntity::class);
        $this->service->enroll(1, 1);
    }

    public function test_enroll_throws_when_after_data_fim(): void
    {
        $t = new Turma('T', 'desc', 10, StatusTurma::Disponivel,
            new DateTime('-30 days'), new DateTime('-5 days'), 1);
        $t->setId(1);
        $this->turmaRepo->method('find')->willReturn($t);

        $this->expectException(UnprocessableEntity::class);
        $this->service->enroll(1, 1);
    }

    public function test_enroll_throws_when_already_enrolled_in_same_course(): void
    {
        $turma    = $this->makeAvailableTurma();
        $existing = new Matricula(1, 1, 1);
        $this->turmaRepo->method('find')->willReturn($turma);
        $this->matriculaRepo->method('findByUsuarioAndCurso')
            ->with(1, 1)->willReturn($existing);

        $this->expectException(UnprocessableEntity::class);
        $this->service->enroll(1, 1);
    }

    public function test_get_by_usuario_delegates_to_repo(): void
    {
        $expected = [new Matricula(1, 2, 3)];
        $this->matriculaRepo->method('getByUsuario')->with(1)->willReturn($expected);
        $this->assertSame($expected, $this->service->getByUsuario(1));
    }
}
```

- [ ] **Step 6: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Services/MatriculaServiceTest.php
```

- [ ] **Step 7: Create MatriculaService**

```php
<?php
// app/Services/MatriculaService.php
namespace App\Services;

use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Contracts\Services\MatriculaServiceInterface;
use App\Entities\Matricula;
use App\Entities\StatusTurma;
use App\Exceptions\Http\UnprocessableEntity;
use DateTime;

class MatriculaService implements MatriculaServiceInterface
{
    public function __construct(
        private MatriculaRepositoryInterface $matriculaRepo,
        private TurmaRepositoryInterface $turmaRepo
    ) {}

    public function enroll(int $usuarioId, int $turmaId): Matricula
    {
        $turma = $this->turmaRepo->find($turmaId);

        if ($turma->getStatus() !== StatusTurma::Disponivel) {
            throw new UnprocessableEntity('Turma está encerrada');
        }

        $now = new DateTime();
        if ($now < $turma->getDataInicio() || $now > $turma->getDataFim()) {
            throw new UnprocessableEntity('Fora do período de matrícula');
        }

        if ($this->matriculaRepo->findByUsuarioAndCurso($usuarioId, $turma->getCursoId()) !== null) {
            throw new UnprocessableEntity('Usuário já matriculado em uma turma deste curso');
        }

        return $this->matriculaRepo->store(new Matricula($usuarioId, $turmaId, $turma->getCursoId()));
    }

    public function getByUsuario(int $usuarioId): array
    {
        return $this->matriculaRepo->getByUsuario($usuarioId);
    }
}
```

- [ ] **Step 8: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/Services/UsuarioServiceTest.php tests/Services/MatriculaServiceTest.php
```

- [ ] **Step 9: Commit**

```bash
git add app/Services/UsuarioService.php app/Services/MatriculaService.php \
        tests/Services/UsuarioServiceTest.php tests/Services/MatriculaServiceTest.php
git commit -m "feat: implement UsuarioService and MatriculaService with enrollment rules"
```

---

## Task 11: CursosController + TurmasController Update

**Files:**
- Create: `app/Http/Controllers/CursosController.php`
- Modify: `app/Http/Controllers/TurmasController.php`
- Test: `tests/Http/Controllers/CursosControllerTest.php`
- Test: `tests/Http/Controllers/TurmasControllerTest.php`

Controller tests use a real `Response` instance bound to a fresh container, and a mocked service.

- [ ] **Step 1: Write failing tests for CursosController**

```php
<?php
// tests/Http/Controllers/CursosControllerTest.php
namespace Tests\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\CursoServiceInterface;
use App\Entities\Curso;
use App\Entities\Temas;
use App\Http\Controllers\CursosController;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;
use PHPUnit\Framework\TestCase;

class CursosControllerTest extends TestCase
{
    private CursoServiceInterface $service;
    private CursosController $controller;

    protected function setUp(): void
    {
        $container = new Container();
        $container->singleton(ResponseInterface::class, fn() => new Response());
        $container->singleton(RequestInterface::class, fn() => new Request());
        Container::setContainer($container);

        $this->service    = $this->createMock(CursoServiceInterface::class);
        $this->controller = new CursosController($this->service);
    }

    private function makeCurso(int $id = 1): Curso
    {
        $c = new Curso('PHP', 'desc', Temas::Tecnologia, 'img.jpg');
        $c->setId($id);
        return $c;
    }

    public function test_index_returns_200_with_list(): void
    {
        $this->service->method('getAvailable')->willReturn([$this->makeCurso()]);
        $response = $this->controller->index();
        $this->assertSame(200, $response->getStatusForTest());
    }

    public function test_store_returns_201(): void
    {
        $this->service->method('store')->willReturn($this->makeCurso());
        $response = $this->controller->store();
        $this->assertSame(201, $response->getStatusForTest());
    }

    public function test_update_returns_200(): void
    {
        $this->service->method('update')->willReturn($this->makeCurso());
        $response = $this->controller->update(1);
        $this->assertSame(200, $response->getStatusForTest());
    }

    public function test_destroy_returns_204(): void
    {
        $response = $this->controller->destroy(1);
        $this->assertSame(204, $response->getStatusForTest());
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Http/Controllers/CursosControllerTest.php
```

- [ ] **Step 3: Create CursosController**

```php
<?php
// app/Http/Controllers/CursosController.php
namespace App\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\CursoServiceInterface;
use App\Support\Container;

class CursosController extends BaseController
{
    public function __construct(private CursoServiceInterface $service) {}

    public function index(): ResponseInterface
    {
        $request = Container::getContainer()->get(RequestInterface::class);
        $filters = array_filter([
            'titulo' => $request->query('titulo'),
            'tema'   => $request->query('tema'),
        ]);
        $cursos = $this->service->getAvailable($filters);
        return $this->response()->success(['data' => array_map([$this, 'serializeCurso'], $cursos)]);
    }

    public function store(): ResponseInterface
    {
        $request = Container::getContainer()->get(RequestInterface::class);
        $data    = $request->getJSON();
        $curso   = $this->service->store($data);
        return $this->response()->created(['data' => $this->serializeCurso($curso)]);
    }

    public function update(int $id): ResponseInterface
    {
        $request = Container::getContainer()->get(RequestInterface::class);
        $data    = $request->getJSON();
        $curso   = $this->service->update($id, $data);
        return $this->response()->success(['data' => $this->serializeCurso($curso)]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }

    private function serializeCurso(\App\Entities\Curso $curso): array
    {
        return [
            'id'         => $curso->getId(),
            'titulo'     => $curso->getTitulo(),
            'descricao'  => $curso->getDescricao(),
            'tema'       => $curso->getTema()->toString(),
            'url_imagem' => $curso->getUrlImagem(),
        ];
    }
}
```

- [ ] **Step 4: Write failing tests for TurmasController**

```php
<?php
// tests/Http/Controllers/TurmasControllerTest.php
namespace Tests\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\TurmaServiceInterface;
use App\Entities\StatusTurma;
use App\Entities\Turma;
use App\Http\Controllers\TurmasController;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;
use DateTime;
use PHPUnit\Framework\TestCase;

class TurmasControllerTest extends TestCase
{
    private TurmaServiceInterface $service;
    private TurmasController $controller;

    protected function setUp(): void
    {
        $container = new Container();
        $container->singleton(ResponseInterface::class, fn() => new Response());
        $container->singleton(RequestInterface::class, fn() => new Request());
        Container::setContainer($container);

        $this->service    = $this->createMock(TurmaServiceInterface::class);
        $this->controller = new TurmasController($this->service);
    }

    private function makeTurma(): Turma
    {
        $t = new Turma('T', 'desc', 30, StatusTurma::Disponivel,
            new DateTime('2026-06-01'), new DateTime('2026-12-01'), 1);
        $t->setId(1);
        return $t;
    }

    public function test_store_returns_201(): void
    {
        $this->service->method('store')->willReturn($this->makeTurma());
        $response = $this->controller->store(1);
        $this->assertSame(201, $response->getStatusForTest());
    }

    public function test_update_returns_200(): void
    {
        $this->service->method('update')->willReturn($this->makeTurma());
        $response = $this->controller->update(1);
        $this->assertSame(200, $response->getStatusForTest());
    }

    public function test_destroy_returns_204(): void
    {
        $response = $this->controller->destroy(1);
        $this->assertSame(204, $response->getStatusForTest());
    }
}
```

- [ ] **Step 5: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Http/Controllers/TurmasControllerTest.php
```

- [ ] **Step 6: Rewrite TurmasController**

```php
<?php
// app/Http/Controllers/TurmasController.php
namespace App\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\TurmaServiceInterface;
use App\Support\Container;

class TurmasController extends BaseController
{
    public function __construct(private TurmaServiceInterface $service) {}

    public function store(int $cursoId): ResponseInterface
    {
        $request = Container::getContainer()->get(RequestInterface::class);
        $data    = $request->getJSON();
        $turma   = $this->service->store($cursoId, $data);
        return $this->response()->created(['data' => $this->serializeTurma($turma)]);
    }

    public function update(int $id): ResponseInterface
    {
        $request = Container::getContainer()->get(RequestInterface::class);
        $data    = $request->getJSON();
        $turma   = $this->service->update($id, $data);
        return $this->response()->success(['data' => $this->serializeTurma($turma)]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }

    private function serializeTurma(\App\Entities\Turma $turma): array
    {
        return [
            'id'               => $turma->getId(),
            'curso_id'         => $turma->getCursoId(),
            'titulo'           => $turma->getTitulo(),
            'descricao'        => $turma->getDescricao(),
            'quantidade_vagas' => $turma->getQuantidadeVagas(),
            'status'           => $turma->getStatus()->toString(),
            'data_inicio'      => $turma->getDataInicio()->format('Y-m-d'),
            'data_fim'         => $turma->getDataFim()->format('Y-m-d'),
        ];
    }
}
```

- [ ] **Step 7: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/Http/Controllers/CursosControllerTest.php tests/Http/Controllers/TurmasControllerTest.php
```

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/CursosController.php app/Http/Controllers/TurmasController.php \
        tests/Http/Controllers/CursosControllerTest.php tests/Http/Controllers/TurmasControllerTest.php
git commit -m "feat: add CursosController and update TurmasController"
```

---

## Task 12: UsuariosController + MatriculasController

**Files:**
- Create: `app/Http/Controllers/UsuariosController.php`
- Create: `app/Http/Controllers/MatriculasController.php`
- Test: `tests/Http/Controllers/UsuariosControllerTest.php`
- Test: `tests/Http/Controllers/MatriculasControllerTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Http/Controllers/UsuariosControllerTest.php
namespace Tests\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\UsuarioServiceInterface;
use App\Entities\Usuario;
use App\Http\Controllers\UsuariosController;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;
use App\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

class UsuariosControllerTest extends TestCase
{
    private UsuarioServiceInterface $service;
    private UsuariosController $controller;

    protected function setUp(): void
    {
        $container = new Container();
        $container->singleton(ResponseInterface::class, fn() => new Response());
        $container->singleton(RequestInterface::class, fn() => new Request());
        Container::setContainer($container);

        $this->service    = $this->createMock(UsuarioServiceInterface::class);
        $this->controller = new UsuariosController($this->service);
    }

    private function makeUsuario(): Usuario
    {
        $u = new Usuario('João', new Email('joao@test.com'));
        $u->setId(1);
        return $u;
    }

    public function test_store_returns_201(): void
    {
        $this->service->method('store')->willReturn($this->makeUsuario());
        $response = $this->controller->store();
        $this->assertSame(201, $response->getStatusForTest());
    }

    public function test_destroy_returns_204(): void
    {
        $response = $this->controller->destroy(1);
        $this->assertSame(204, $response->getStatusForTest());
    }
}
```

```php
<?php
// tests/Http/Controllers/MatriculasControllerTest.php
namespace Tests\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\MatriculaServiceInterface;
use App\Entities\Matricula;
use App\Http\Controllers\MatriculasController;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;
use PHPUnit\Framework\TestCase;

class MatriculasControllerTest extends TestCase
{
    private MatriculaServiceInterface $service;
    private MatriculasController $controller;

    protected function setUp(): void
    {
        $container = new Container();
        $container->singleton(ResponseInterface::class, fn() => new Response());
        $container->singleton(RequestInterface::class, fn() => new Request());
        Container::setContainer($container);

        $this->service    = $this->createMock(MatriculaServiceInterface::class);
        $this->controller = new MatriculasController($this->service);
    }

    public function test_store_returns_201(): void
    {
        $m = new Matricula(1, 2, 3);
        $m->setId(1);
        $this->service->method('enroll')->willReturn($m);
        $response = $this->controller->store();
        $this->assertSame(201, $response->getStatusForTest());
    }

    public function test_index_returns_200_with_list(): void
    {
        $this->service->method('getByUsuario')->with(1)->willReturn([]);
        $response = $this->controller->index(1);
        $this->assertSame(200, $response->getStatusForTest());
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
./vendor/bin/phpunit tests/Http/Controllers/UsuariosControllerTest.php tests/Http/Controllers/MatriculasControllerTest.php
```

- [ ] **Step 3: Create UsuariosController**

```php
<?php
// app/Http/Controllers/UsuariosController.php
namespace App\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\UsuarioServiceInterface;
use App\Support\Container;

class UsuariosController extends BaseController
{
    public function __construct(private UsuarioServiceInterface $service) {}

    public function store(): ResponseInterface
    {
        $request  = Container::getContainer()->get(RequestInterface::class);
        $data     = $request->getJSON();
        $usuario  = $this->service->store($data);
        return $this->response()->created([
            'data' => [
                'id'    => $usuario->getId(),
                'nome'  => $usuario->getNome(),
                'email' => $usuario->getEmail()->getValue(),
            ],
        ]);
    }

    public function destroy(int $id): ResponseInterface
    {
        $this->service->destroy($id);
        return $this->response()->noContent();
    }
}
```

- [ ] **Step 4: Create MatriculasController**

```php
<?php
// app/Http/Controllers/MatriculasController.php
namespace App\Http\Controllers;

use App\Contracts\RequestInterface;
use App\Contracts\ResponseInterface;
use App\Contracts\Services\MatriculaServiceInterface;
use App\Support\Container;

class MatriculasController extends BaseController
{
    public function __construct(private MatriculaServiceInterface $service) {}

    public function store(): ResponseInterface
    {
        $request    = Container::getContainer()->get(RequestInterface::class);
        $data       = $request->getJSON();
        $matricula  = $this->service->enroll((int) $data['usuario_id'], (int) $data['turma_id']);
        return $this->response()->created([
            'data' => [
                'id'          => $matricula->getId(),
                'usuario_id'  => $matricula->getUsuarioId(),
                'turma_id'    => $matricula->getTurmaId(),
                'curso_id'    => $matricula->getCursoId(),
            ],
        ]);
    }

    public function index(int $usuarioId): ResponseInterface
    {
        $matriculas = $this->service->getByUsuario($usuarioId);
        return $this->response()->success(['data' => $matriculas]);
    }
}
```

- [ ] **Step 5: Run — expect PASS**

```bash
./vendor/bin/phpunit tests/Http/Controllers/UsuariosControllerTest.php tests/Http/Controllers/MatriculasControllerTest.php
```

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/UsuariosController.php app/Http/Controllers/MatriculasController.php \
        tests/Http/Controllers/UsuariosControllerTest.php tests/Http/Controllers/MatriculasControllerTest.php
git commit -m "feat: add UsuariosController and MatriculasController"
```

---

## Task 13: Wire — Routes + AppServiceProvider + config.php

**Files:**
- Modify: `routes/api.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `config.php`

No new tests — wiring only. Run the full suite after.

- [ ] **Step 1: Update config.php**

```php
<?php
// config.php
return [
    'LOG_PATH' => __DIR__ . '/storage/logs',
    'db' => [
        'dsn'  => 'mysql:host=db;dbname=dotgroup;charset=utf8mb4',
        'user' => 'dotgroup',
        'pass' => 'dotgroup',
    ],
];
```

Note: values match the docker-compose MySQL service. Adjust if your environment differs.

- [ ] **Step 2: Update routes/api.php**

```php
<?php
// routes/api.php
use App\Http\Controllers\CursosController;
use App\Http\Controllers\MatriculasController;
use App\Http\Controllers\TurmasController;
use App\Http\Controllers\UsuariosController;

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

- [ ] **Step 3: Update AppServiceProvider**

```php
<?php
// app/Providers/AppServiceProvider.php
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
                $c->get(ConfigInterface::class)->get('db.dsn'),
                $c->get(ConfigInterface::class)->get('db.user'),
                $c->get(ConfigInterface::class)->get('db.pass'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            )
        );

        // Repositories
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

        // Services
        $this->container->bind(
            CursoServiceInterface::class,
            fn(Container $c) => new CursoService($c->get(CursoRepositoryInterface::class))
        );
        $this->container->bind(
            TurmaServiceInterface::class,
            fn(Container $c) => new TurmaService(
                $c->get(TurmaRepositoryInterface::class),
                $c->get(CursoRepositoryInterface::class)
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
```

- [ ] **Step 4: Verify Config::get supports dot notation**

Check that `Config::get('db.dsn')` works. Run:
```bash
grep -n 'function get' app/Support/Config.php
```
If Config does not support dot notation (e.g. `db.dsn`), replace `$c->get(ConfigInterface::class)->get('db.dsn')` with `$c->get(ConfigInterface::class)->get('db')['dsn']` in AppServiceProvider.

- [ ] **Step 5: Run full test suite**

```bash
./vendor/bin/phpunit
```
Expected: all tests green.

- [ ] **Step 6: Commit**

```bash
git add config.php routes/api.php app/Providers/AppServiceProvider.php
git commit -m "feat: wire routes, repositories, and services in AppServiceProvider"
```

---

## Self-Review Checklist

- [x] Use case 1 (create curso + turmas): Tasks 5–6 (repos) + 9 (services) + 11 (controllers) + 13 (routes)
- [x] Use case 2 (list cursos with available turmas, filters): `getWithAvailableTurmas` in Task 5, `getAvailable` in Task 9, `index` in Task 11
- [x] Use case 3 (enroll user): `MatriculaService::enroll` in Task 10, `MatriculasController::store` in Task 12
- [x] Use case 4 (reject closed/out-of-date turmas): Tests in Task 10 Steps 5–6
- [x] Use case 5 (list courses user is enrolled in): `MatriculaService::getByUsuario` + `MatriculasController::index`
- [x] Use case 6 (reject duplicate enrollment per course): `findByUsuarioAndCurso` check in Task 10
- [x] Tipo consistency: `TurmaService::store(int $cursoId, mixed $data)` updated in interface at Task 9 Step 7
- [x] `Config::get` dot-notation check included in Task 13 Step 4
