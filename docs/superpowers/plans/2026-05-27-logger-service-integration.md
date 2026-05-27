# Logger Service Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrate the existing Logger into all four services for write-operation and exception tracing, and switch Logger to daily rotating log files.

**Architecture:** Logger gets `RotatingFileHandler` so each day produces its own file. Each service receives `LoggerInterface` via constructor injection. `AppServiceProvider` passes the container-registered logger to each service binding. TDD throughout — test changes precede implementation changes.

**Tech Stack:** PHP 8, Monolog 1.25 (`RotatingFileHandler`), PSR-3 `LoggerInterface`, PHPUnit 11 mocks.

---

## File Map

| File | Change |
|------|--------|
| `app/Support/Logger.php` | `StreamHandler` → `RotatingFileHandler` |
| `tests/Support/LoggerTest.php` | Fix tearDown + assertions for rotating filename |
| `app/Services/CursoService.php` | Add `LoggerInterface` param, log store/update/destroy |
| `tests/Services/CursoServiceTest.php` | Add logger mock + logging assertions |
| `app/Services/TurmaService.php` | Add `LoggerInterface` param, log store/update/destroy |
| `tests/Services/TurmaServiceTest.php` | Add logger mock + logging assertions |
| `app/Services/MatriculaService.php` | Add `LoggerInterface` param, log enroll/updateStatus/destroy + exceptions |
| `tests/Services/MatriculaServiceTest.php` | Add logger mock + logging assertions |
| `app/Services/UsuarioService.php` | Add `LoggerInterface` param, log store/update/destroy + exceptions |
| `tests/Services/UsuarioServiceTest.php` | Add logger mock + logging assertions |
| `app/Providers/AppServiceProvider.php` | Pass `LoggerInterface` to all four service bindings |

---

## Task 1: Logger — Daily Rotation

**Files:**
- Modify: `tests/Support/LoggerTest.php`
- Modify: `app/Support/Logger.php`

- [ ] **Step 1: Update LoggerTest to expect rotating filename and run to see failures**

Replace the full content of `tests/Support/LoggerTest.php`:

```php
<?php

namespace Tests\Support;

use App\Support\Config;
use App\Support\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggerTest extends TestCase
{
    private string $logPath;

    protected function setUp(): void
    {
        $this->logPath = sys_get_temp_dir() . '/dot-group-test-logs';
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        foreach (glob("{$this->logPath}/app-*.log") as $file) {
            unlink($file);
        }
        if (is_dir($this->logPath)) {
            rmdir($this->logPath);
        }
    }

    public function test_implements_psr3_logger_interface(): void
    {
        $config = new Config(['LOG_PATH' => $this->logPath]);
        $logger = new Logger($config);
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function test_writes_info_message_to_log_file(): void
    {
        $config = new Config(['LOG_PATH' => $this->logPath]);
        $logger = new Logger($config);

        $logger->info('test message');

        $today   = date('Y-m-d');
        $logFile = "{$this->logPath}/app-{$today}.log";
        $this->assertFileExists($logFile);
        $this->assertStringContainsString('test message', file_get_contents($logFile));
    }

    public function test_log_file_name_contains_current_date(): void
    {
        $config = new Config(['LOG_PATH' => $this->logPath]);
        $logger = new Logger($config);
        $logger->info('date check');

        $today = date('Y-m-d');
        $files = glob("{$this->logPath}/app-*.log");
        $this->assertCount(1, $files);
        $this->assertStringContainsString($today, $files[0]);
    }

    public function test_uses_default_log_path_when_not_configured(): void
    {
        $config = new Config([]);
        $logger = new Logger($config);
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }
}
```

- [ ] **Step 2: Run the updated tests to confirm failures**

```bash
./vendor/bin/phpunit tests/Support/LoggerTest.php --no-coverage
```

Expected: `test_writes_info_message_to_log_file` and `test_log_file_name_contains_current_date` FAIL because `app.log` exists, not `app-YYYY-MM-DD.log`.

- [ ] **Step 3: Update Logger to use RotatingFileHandler**

Replace the full content of `app/Support/Logger.php`:

```php
<?php

namespace App\Support;

use App\Contracts\ConfigInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as MonoLogger;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Wrapper de logger que encapsula o Monolog como driver interno.
 *
 * Estende AbstractLogger do PSR-3 para implementar apenas o método log(),
 * enquanto os demais métodos de conveniência (info, error, debug, etc.)
 * são herdados automaticamente. Isso desacopla o código da aplicação do
 * Monolog diretamente: se quisermos trocar o driver no futuro, basta
 * alterar esta classe sem tocar em nada que usa LoggerInterface.
 *
 * O caminho do arquivo de log é lido da configuração via LOG_PATH,
 * com fallback para storage/logs dentro do projeto. Um novo arquivo
 * é criado por dia (app-YYYY-MM-DD.log) via RotatingFileHandler.
 */
class Logger extends AbstractLogger
{
    /** Driver concreto de log (Monolog) que processa as mensagens. */
    private LoggerInterface $driver;

    /**
     * @param ConfigInterface $config Configurações da aplicação, usadas para obter LOG_PATH.
     */
    public function __construct(ConfigInterface $config)
    {
        $logPath = $config->get('LOG_PATH', __DIR__ . '/../../storage/logs');

        $monolog = new MonoLogger('app');
        $monolog->pushHandler(new RotatingFileHandler("{$logPath}/app.log", maxFiles: 0));

        $this->driver = $monolog;
    }

    /**
     * Delega o registro da mensagem ao driver interno.
     *
     * Assinatura sem type hints em $level e $message para manter compatibilidade
     * com psr/log ~1.0, que não declara tipos nesses parâmetros.
     *
     * @param mixed                $level   Nível do log (ex: 'info', 'error').
     * @param mixed                $message Mensagem a registrar.
     * @param array<string, mixed> $context Dados de contexto opcionais.
     */
    public function log($level, $message, array $context = []): void
    {
        $this->driver->log($level, $message, $context);
    }
}
```

- [ ] **Step 4: Run tests again to confirm all pass**

```bash
./vendor/bin/phpunit tests/Support/LoggerTest.php --no-coverage
```

Expected: 4 tests, 0 failures.

- [ ] **Step 5: Commit**

```bash
git add app/Support/Logger.php tests/Support/LoggerTest.php
git commit -m "feat: switch Logger to daily rotating log files"
```

---

## Task 2: CursoService — Logger Injection

**Files:**
- Modify: `tests/Services/CursoServiceTest.php`
- Modify: `app/Services/CursoService.php`

- [ ] **Step 1: Add logger mock and failing logging tests to CursoServiceTest**

Replace `setUp` and add new test methods at the end of `tests/Services/CursoServiceTest.php`:

```php
<?php

namespace Tests\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Entities\Curso;
use App\Entities\Temas;
use App\Exceptions\Http\NotFound;
use App\Services\CursoService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CursoServiceTest extends TestCase
{
    private CursoRepositoryInterface $repo;
    private LoggerInterface $logger;
    private CursoService $service;

    protected function setUp(): void
    {
        $this->repo    = $this->createMock(CursoRepositoryInterface::class);
        $this->logger  = $this->createMock(LoggerInterface::class);
        $this->service = new CursoService($this->repo, $this->logger);
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
        $this->repo->expects($this->once())->method('store')
            ->with($this->isInstanceOf(Curso::class))
            ->willReturn($curso);
        $this->assertSame($curso, $this->service->store($data));
    }

    public function test_update_delegates_to_repo(): void
    {
        $data     = ['titulo' => 'New', 'descricao' => 'd', 'tema' => 'agro', 'url_imagem' => 'i.jpg'];
        $existing = $this->makeCurso();
        $updated  = $this->makeCurso();
        $this->repo->expects($this->once())->method('find')->with(1)->willReturn($existing);
        $this->repo->expects($this->once())->method('update')
            ->with(1, $this->isInstanceOf(Curso::class))
            ->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, $data));
    }

    public function test_update_partial_falls_back_to_existing_fields(): void
    {
        $existing = $this->makeCurso();
        $updated  = $this->makeCurso();
        $this->repo->method('find')->with(1)->willReturn($existing);
        $this->repo->expects($this->once())->method('update')
            ->with(1, $this->callback(function (Curso $c) use ($existing) {
                return $c->getTitulo()    === 'Novo Titulo'
                    && $c->getDescricao() === $existing->getDescricao()
                    && $c->getTema()      === $existing->getTema()
                    && $c->getUrlImagem() === $existing->getUrlImagem();
            }))
            ->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, ['titulo' => 'Novo Titulo']));
    }

    public function test_destroy_delegates_to_repo(): void
    {
        $this->repo->expects($this->once())->method('destroy')->with(1);
        $this->service->destroy(1);
    }

    public function test_store_logs_info_on_success(): void
    {
        $curso = $this->makeCurso(42);
        $this->repo->method('store')->willReturn($curso);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('CursoService::store cursoId=42'));
        $this->service->store(['titulo' => 'PHP', 'descricao' => 'd', 'tema' => 'tecnologia', 'url_imagem' => 'i.jpg']);
    }

    public function test_update_logs_info_on_success(): void
    {
        $existing = $this->makeCurso(3);
        $updated  = $this->makeCurso(3);
        $this->repo->method('find')->willReturn($existing);
        $this->repo->method('update')->willReturn($updated);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('CursoService::update cursoId=3'));
        $this->service->update(3, ['titulo' => 'Novo']);
    }

    public function test_destroy_logs_info_on_success(): void
    {
        $this->repo->method('destroy')->with(7);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('CursoService::destroy cursoId=7'));
        $this->service->destroy(7);
    }
}
```

- [ ] **Step 2: Run tests to confirm failures**

```bash
./vendor/bin/phpunit tests/Services/CursoServiceTest.php --no-coverage
```

Expected: Multiple failures — `CursoService` constructor does not accept a second argument.

- [ ] **Step 3: Update CursoService to inject and use logger**

Replace the full content of `app/Services/CursoService.php`:

```php
<?php

namespace App\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Services\CursoServiceInterface;
use App\Entities\Curso;
use App\Entities\Temas;
use Psr\Log\LoggerInterface;

class CursoService implements CursoServiceInterface
{
    public function __construct(
        private CursoRepositoryInterface $repo,
        private LoggerInterface $logger
    ) {
    }

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
        $curso = new Curso(
            $data['titulo'],
            $data['descricao'],
            Temas::fromString($data['tema']),
            $data['url_imagem']
        );

        $result = $this->repo->store($curso);
        $this->logger->info("CursoService::store cursoId={$result->getId()}");
        return $result;
    }

    public function update(int $id, mixed $data): Curso
    {
        $existing = $this->repo->find($id);

        $curso = new Curso(
            $data['titulo']     ?? $existing->getTitulo(),
            $data['descricao']  ?? $existing->getDescricao(),
            isset($data['tema']) ? Temas::fromString($data['tema']) : $existing->getTema(),
            $data['url_imagem'] ?? $existing->getUrlImagem()
        );

        $result = $this->repo->update($id, $curso);
        $this->logger->info("CursoService::update cursoId={$id}");
        return $result;
    }

    public function destroy(int $id): void
    {
        $this->repo->destroy($id);
        $this->logger->info("CursoService::destroy cursoId={$id}");
    }
}
```

- [ ] **Step 4: Run tests to confirm all pass**

```bash
./vendor/bin/phpunit tests/Services/CursoServiceTest.php --no-coverage
```

Expected: All tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Services/CursoService.php tests/Services/CursoServiceTest.php
git commit -m "feat: inject logger into CursoService and log write operations"
```

---

## Task 3: TurmaService — Logger Injection

**Files:**
- Modify: `tests/Services/TurmaServiceTest.php`
- Modify: `app/Services/TurmaService.php`

- [ ] **Step 1: Add logger mock and failing logging tests to TurmaServiceTest**

Replace the full content of `tests/Services/TurmaServiceTest.php`:

```php
<?php

namespace Tests\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Entities\Curso;
use App\Entities\StatusTurma;
use App\Entities\Temas;
use App\Entities\Turma;
use App\Exceptions\Http\NotFound;
use App\Services\TurmaService;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TurmaServiceTest extends TestCase
{
    private TurmaRepositoryInterface $turmaRepo;
    private CursoRepositoryInterface $cursoRepo;
    private MatriculaRepositoryInterface $matriculaRepo;
    private LoggerInterface $logger;
    private TurmaService $service;

    protected function setUp(): void
    {
        $this->turmaRepo     = $this->createMock(TurmaRepositoryInterface::class);
        $this->cursoRepo     = $this->createMock(CursoRepositoryInterface::class);
        $this->matriculaRepo = $this->createMock(MatriculaRepositoryInterface::class);
        $this->logger        = $this->createMock(LoggerInterface::class);
        $this->service       = new TurmaService(
            $this->turmaRepo,
            $this->cursoRepo,
            $this->matriculaRepo,
            $this->logger
        );
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
            ->with($this->isInstanceOf(Turma::class))
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
        $existing = $this->makeTurma();
        $this->turmaRepo->method('find')->with(1)->willReturn($existing);

        $data    = ['titulo' => 'T2', 'descricao' => 'd', 'quantidade_vagas' => 5,
                    'status' => 'encerrado', 'data_inicio' => '2026-06-01', 'data_fim' => '2026-12-01'];
        $updated = $this->makeTurma();
        $this->turmaRepo->expects($this->once())->method('update')
            ->with(1, $this->isInstanceOf(Turma::class))
            ->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, $data));
    }

    public function test_update_partial_falls_back_to_existing_fields(): void
    {
        $existing = $this->makeTurma();
        $updated  = $this->makeTurma();
        $this->turmaRepo->method('find')->with(1)->willReturn($existing);
        $this->turmaRepo->expects($this->once())->method('update')
            ->with(1, $this->callback(function (Turma $t) use ($existing) {
                return $t->getTitulo()          === 'Novo Titulo'
                    && $t->getDescricao()       === $existing->getDescricao()
                    && $t->getQuantidadeVagas() === $existing->getQuantidadeVagas()
                    && $t->getStatus()          === $existing->getStatus()
                    && $t->getCursoId()         === $existing->getCursoId();
            }))
            ->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, ['titulo' => 'Novo Titulo']));
    }

    public function test_destroy_delegates_to_repo(): void
    {
        $this->turmaRepo->expects($this->once())->method('destroy')->with(1);
        $this->service->destroy(1);
    }

    public function test_update_calls_inativar_by_turma_when_transitioning_to_encerrado(): void
    {
        $existing = $this->makeTurma(id: 1);
        $this->turmaRepo->method('find')->with(1)->willReturn($existing);

        $encerrada = new Turma('T', 'desc', 30, StatusTurma::Encerrado,
            new DateTime('2026-06-01'), new DateTime('2026-12-01'), 1);
        $encerrada->setId(1);
        $this->turmaRepo->method('update')->willReturn($encerrada);

        $this->matriculaRepo->expects($this->once())
            ->method('inativarByTurma')
            ->with(1);

        $this->service->update(1, ['status' => 'encerrado']);
    }

    public function test_update_does_not_call_inativar_when_already_encerrada(): void
    {
        $existing = new Turma('T', 'desc', 30, StatusTurma::Encerrado,
            new DateTime('2026-06-01'), new DateTime('2026-12-01'), 1);
        $existing->setId(1);
        $this->turmaRepo->method('find')->with(1)->willReturn($existing);

        $encerrada = clone $existing;
        $this->turmaRepo->method('update')->willReturn($encerrada);

        $this->matriculaRepo->expects($this->never())->method('inativarByTurma');

        $this->service->update(1, ['status' => 'encerrado']);
    }

    public function test_store_logs_info_on_success(): void
    {
        $turma = $this->makeTurma(5);
        $this->cursoRepo->method('find')->willReturn($this->makeCurso());
        $this->turmaRepo->method('store')->willReturn($turma);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('TurmaService::store turmaId=5'));
        $this->service->store(1, [
            'titulo' => 'T', 'descricao' => 'd', 'quantidade_vagas' => 10,
            'status' => 'disponivel', 'data_inicio' => '2026-06-01', 'data_fim' => '2026-12-01',
        ]);
    }

    public function test_update_logs_info_on_success(): void
    {
        $existing = $this->makeTurma(3);
        $updated  = $this->makeTurma(3);
        $this->turmaRepo->method('find')->willReturn($existing);
        $this->turmaRepo->method('update')->willReturn($updated);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('TurmaService::update turmaId=3'));
        $this->service->update(3, ['titulo' => 'Novo']);
    }

    public function test_destroy_logs_info_on_success(): void
    {
        $this->turmaRepo->method('destroy')->with(9);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('TurmaService::destroy turmaId=9'));
        $this->service->destroy(9);
    }
}
```

- [ ] **Step 2: Run tests to confirm failures**

```bash
./vendor/bin/phpunit tests/Services/TurmaServiceTest.php --no-coverage
```

Expected: Failures — `TurmaService` does not accept a fourth argument.

- [ ] **Step 3: Update TurmaService to inject and use logger**

Replace the full content of `app/Services/TurmaService.php`:

```php
<?php

namespace App\Services;

use App\Contracts\Repositories\CursoRepositoryInterface;
use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Contracts\Services\TurmaServiceInterface;
use App\Entities\StatusTurma;
use App\Entities\Turma;
use DateTime;
use Psr\Log\LoggerInterface;

class TurmaService implements TurmaServiceInterface
{
    public function __construct(
        private TurmaRepositoryInterface $turmaRepo,
        private CursoRepositoryInterface $cursoRepo,
        private MatriculaRepositoryInterface $matriculaRepo,
        private LoggerInterface $logger
    ) {
    }

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

        $turma = new Turma(
            $data['titulo'],
            $data['descricao'],
            (int) $data['quantidade_vagas'],
            StatusTurma::fromString($data['status']),
            new DateTime($data['data_inicio']),
            new DateTime($data['data_fim']),
            $cursoId
        );

        $result = $this->turmaRepo->store($turma);
        $this->logger->info("TurmaService::store turmaId={$result->getId()}");
        return $result;
    }

    public function update(int $id, mixed $data): Turma
    {
        $existing = $this->turmaRepo->find($id);

        $newStatus = isset($data['status'])
            ? StatusTurma::fromString($data['status'])
            : $existing->getStatus();

        $turma = new Turma(
            $data['titulo']          ?? $existing->getTitulo(),
            $data['descricao']       ?? $existing->getDescricao(),
            isset($data['quantidade_vagas']) ? (int) $data['quantidade_vagas'] : $existing->getQuantidadeVagas(),
            $newStatus,
            isset($data['data_inicio']) ? new DateTime($data['data_inicio']) : $existing->getDataInicio(),
            isset($data['data_fim'])    ? new DateTime($data['data_fim'])    : $existing->getDataFim(),
            $existing->getCursoId()
        );

        $updated = $this->turmaRepo->update($id, $turma);
        $this->logger->info("TurmaService::update turmaId={$id}");

        if ($existing->getStatus() !== StatusTurma::Encerrado && $newStatus === StatusTurma::Encerrado) {
            $this->matriculaRepo->inativarByTurma($id);
        }

        return $updated;
    }

    public function destroy(int $id): void
    {
        $this->turmaRepo->destroy($id);
        $this->logger->info("TurmaService::destroy turmaId={$id}");
    }
}
```

- [ ] **Step 4: Run tests to confirm all pass**

```bash
./vendor/bin/phpunit tests/Services/TurmaServiceTest.php --no-coverage
```

Expected: All tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Services/TurmaService.php tests/Services/TurmaServiceTest.php
git commit -m "feat: inject logger into TurmaService and log write operations"
```

---

## Task 4: MatriculaService — Logger Injection + Exception Logging

**Files:**
- Modify: `tests/Services/MatriculaServiceTest.php`
- Modify: `app/Services/MatriculaService.php`

- [ ] **Step 1: Add logger mock and failing logging tests to MatriculaServiceTest**

Replace the full content of `tests/Services/MatriculaServiceTest.php`:

```php
<?php

namespace Tests\Services;

use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Entities\Matricula;
use App\Entities\StatusMatricula;
use App\Entities\StatusTurma;
use App\Entities\Turma;
use App\Exceptions\Http\NotFound;
use App\Exceptions\Http\UnprocessableEntity;
use App\Services\MatriculaService;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MatriculaServiceTest extends TestCase
{
    private MatriculaRepositoryInterface $matriculaRepo;
    private TurmaRepositoryInterface $turmaRepo;
    private LoggerInterface $logger;
    private MatriculaService $service;

    protected function setUp(): void
    {
        $this->matriculaRepo = $this->createMock(MatriculaRepositoryInterface::class);
        $this->turmaRepo     = $this->createMock(TurmaRepositoryInterface::class);
        $this->logger        = $this->createMock(LoggerInterface::class);
        $this->service       = new MatriculaService($this->matriculaRepo, $this->turmaRepo, $this->logger);
    }

    private function makeAvailableTurma(int $id = 1, int $cursoId = 1): Turma
    {
        $t = new Turma('T', 'desc', 30, StatusTurma::Disponivel,
            new DateTime('-1 day'), new DateTime('+1 day'), $cursoId);
        $t->setId($id);
        return $t;
    }

    private function makeMatricula(int $id = 1, int $turmaId = 1): Matricula
    {
        $m = new Matricula(1, $turmaId, 1);
        $m->setId($id);
        return $m;
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

    public function test_destroy_delegates_to_repo(): void
    {
        $this->matriculaRepo->expects($this->once())->method('destroy')->with(5);
        $this->service->destroy(5);
    }

    public function test_update_status_returns_updated_matricula(): void
    {
        $matricula = $this->makeMatricula(turmaId: 1);
        $turma     = $this->makeAvailableTurma(id: 1);
        $updated   = $this->makeMatricula(turmaId: 1);
        $updated->setStatus(StatusMatricula::Inativo);

        $this->matriculaRepo->method('find')->with(1)->willReturn($matricula);
        $this->turmaRepo->method('find')->with(1)->willReturn($turma);
        $this->matriculaRepo->method('updateStatus')
            ->with(1, StatusMatricula::Inativo)
            ->willReturn($updated);

        $result = $this->service->updateStatus(1, 'inativo');
        $this->assertSame(StatusMatricula::Inativo, $result->getStatus());
    }

    public function test_update_status_throws_when_matricula_not_found(): void
    {
        $this->matriculaRepo->method('find')->willThrowException(new NotFound());
        $this->expectException(NotFound::class);
        $this->service->updateStatus(99, 'ativo');
    }

    public function test_update_status_throws_when_turma_encerrada(): void
    {
        $matricula = $this->makeMatricula(turmaId: 1);
        $turma     = new Turma('T', 'desc', 10, StatusTurma::Encerrado,
            new DateTime('-30 days'), new DateTime('-1 day'), 1);
        $turma->setId(1);

        $this->matriculaRepo->method('find')->willReturn($matricula);
        $this->turmaRepo->method('find')->willReturn($turma);

        $this->expectException(UnprocessableEntity::class);
        $this->service->updateStatus(1, 'cancelado');
    }

    public function test_update_status_throws_for_invalid_status_string(): void
    {
        $matricula = $this->makeMatricula(turmaId: 1);
        $turma     = $this->makeAvailableTurma(id: 1);

        $this->matriculaRepo->method('find')->willReturn($matricula);
        $this->turmaRepo->method('find')->willReturn($turma);

        $this->expectException(UnprocessableEntity::class);
        $this->service->updateStatus(1, 'invalido');
    }

    public function test_enroll_logs_info_on_success(): void
    {
        $turma = $this->makeAvailableTurma(id: 3);
        $this->turmaRepo->method('find')->willReturn($turma);
        $this->matriculaRepo->method('findByUsuarioAndCurso')->willReturn(null);

        $stored = new Matricula(5, 3, 1);
        $stored->setId(42);
        $this->matriculaRepo->method('store')->willReturn($stored);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('MatriculaService::enroll usuarioId=5 turmaId=3 → matriculaId=42'));

        $this->service->enroll(5, 3);
    }

    public function test_enroll_logs_error_on_turma_encerrada(): void
    {
        $t = new Turma('T', 'desc', 10, StatusTurma::Encerrado,
            new DateTime('-1 day'), new DateTime('+1 day'), 1);
        $t->setId(1);
        $this->turmaRepo->method('find')->willReturn($t);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('MatriculaService::enroll FAILED: Turma está encerrada'));

        $this->expectException(UnprocessableEntity::class);
        $this->service->enroll(1, 1);
    }

    public function test_enroll_logs_error_on_periodo_invalido(): void
    {
        $t = new Turma('T', 'desc', 10, StatusTurma::Disponivel,
            new DateTime('+5 days'), new DateTime('+30 days'), 1);
        $t->setId(1);
        $this->turmaRepo->method('find')->willReturn($t);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('MatriculaService::enroll FAILED:'));

        $this->expectException(UnprocessableEntity::class);
        $this->service->enroll(1, 1);
    }

    public function test_updateStatus_logs_info_on_success(): void
    {
        $matricula = $this->makeMatricula(id: 7, turmaId: 1);
        $turma     = $this->makeAvailableTurma(id: 1);
        $updated   = $this->makeMatricula(id: 7, turmaId: 1);
        $updated->setStatus(StatusMatricula::Inativo);

        $this->matriculaRepo->method('find')->willReturn($matricula);
        $this->turmaRepo->method('find')->willReturn($turma);
        $this->matriculaRepo->method('updateStatus')->willReturn($updated);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('MatriculaService::updateStatus matriculaId=7 → Inativo'));

        $this->service->updateStatus(7, 'inativo');
    }

    public function test_updateStatus_logs_error_on_invalid_status(): void
    {
        $matricula = $this->makeMatricula(turmaId: 1);
        $turma     = $this->makeAvailableTurma(id: 1);

        $this->matriculaRepo->method('find')->willReturn($matricula);
        $this->turmaRepo->method('find')->willReturn($turma);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('MatriculaService::updateStatus FAILED:'));

        $this->expectException(UnprocessableEntity::class);
        $this->service->updateStatus(1, 'invalido');
    }

    public function test_destroy_logs_info_on_success(): void
    {
        $this->matriculaRepo->method('destroy')->with(5);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('MatriculaService::destroy matriculaId=5'));
        $this->service->destroy(5);
    }
}
```

- [ ] **Step 2: Run tests to confirm failures**

```bash
./vendor/bin/phpunit tests/Services/MatriculaServiceTest.php --no-coverage
```

Expected: Failures — `MatriculaService` does not accept a third argument.

- [ ] **Step 3: Update MatriculaService to inject and use logger**

Replace the full content of `app/Services/MatriculaService.php`:

```php
<?php

namespace App\Services;

use App\Contracts\Repositories\MatriculaRepositoryInterface;
use App\Contracts\Repositories\TurmaRepositoryInterface;
use App\Contracts\Services\MatriculaServiceInterface;
use App\Entities\Matricula;
use App\Entities\StatusMatricula;
use App\Entities\StatusTurma;
use App\Exceptions\Http\UnprocessableEntity;
use DateTime;
use Psr\Log\LoggerInterface;

class MatriculaService implements MatriculaServiceInterface
{
    public function __construct(
        private MatriculaRepositoryInterface $matriculaRepo,
        private TurmaRepositoryInterface $turmaRepo,
        private LoggerInterface $logger
    ) {
    }

    public function enroll(int $usuarioId, int $turmaId): Matricula
    {
        $turma = $this->turmaRepo->find($turmaId);

        try {
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
        } catch (UnprocessableEntity $e) {
            $this->logger->error("MatriculaService::enroll FAILED: {$e->getMessage()}");
            throw $e;
        }

        $result = $this->matriculaRepo->store(new Matricula($usuarioId, $turmaId, $turma->getCursoId()));
        $this->logger->info("MatriculaService::enroll usuarioId={$usuarioId} turmaId={$turmaId} → matriculaId={$result->getId()}");
        return $result;
    }

    public function getByUsuario(int $usuarioId): array
    {
        return $this->matriculaRepo->getByUsuario($usuarioId);
    }

    public function updateStatus(int $id, string $status): Matricula
    {
        $matricula = $this->matriculaRepo->find($id);
        $turma     = $this->turmaRepo->find($matricula->getTurmaId());

        try {
            if ($turma->getStatus() === StatusTurma::Encerrado) {
                throw new UnprocessableEntity('Turma encerrada');
            }

            try {
                $statusEnum = StatusMatricula::fromString($status);
            } catch (\ValueError) {
                throw new UnprocessableEntity("Status inválido: {$status}");
            }
        } catch (UnprocessableEntity $e) {
            $this->logger->error("MatriculaService::updateStatus FAILED: {$e->getMessage()}");
            throw $e;
        }

        $result = $this->matriculaRepo->updateStatus($id, $statusEnum);
        $this->logger->info("MatriculaService::updateStatus matriculaId={$id} → {$statusEnum->name}");
        return $result;
    }

    public function destroy(int $id): void
    {
        $this->matriculaRepo->destroy($id);
        $this->logger->info("MatriculaService::destroy matriculaId={$id}");
    }
}
```

- [ ] **Step 4: Run tests to confirm all pass**

```bash
./vendor/bin/phpunit tests/Services/MatriculaServiceTest.php --no-coverage
```

Expected: All tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Services/MatriculaService.php tests/Services/MatriculaServiceTest.php
git commit -m "feat: inject logger into MatriculaService and log write operations and exceptions"
```

---

## Task 5: UsuarioService — Logger Injection + Exception Logging

**Files:**
- Modify: `tests/Services/UsuarioServiceTest.php`
- Modify: `app/Services/UsuarioService.php`

- [ ] **Step 1: Add logger mock and failing logging tests to UsuarioServiceTest**

Replace the full content of `tests/Services/UsuarioServiceTest.php`:

```php
<?php

namespace Tests\Services;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Entities\Usuario;
use App\Exceptions\Http\NotFound;
use App\Exceptions\Http\UnprocessableEntity;
use App\Services\UsuarioService;
use App\ValueObjects\Email;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UsuarioServiceTest extends TestCase
{
    private UsuarioRepositoryInterface $repo;
    private LoggerInterface $logger;
    private UsuarioService $service;

    protected function setUp(): void
    {
        $this->repo    = $this->createMock(UsuarioRepositoryInterface::class);
        $this->logger  = $this->createMock(LoggerInterface::class);
        $this->service = new UsuarioService($this->repo, $this->logger);
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
        $this->repo->expects($this->once())->method('store')
            ->with($this->isInstanceOf(Usuario::class))
            ->willReturn($usuario);
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

    public function test_update_delegates_to_repo(): void
    {
        $existing = $this->makeUsuario();
        $updated  = $this->makeUsuario();
        $data     = ['nome' => 'Maria', 'email' => 'maria@test.com'];
        $this->repo->expects($this->once())->method('find')->with(1)->willReturn($existing);
        $this->repo->expects($this->once())->method('update')
            ->with(1, $this->isInstanceOf(Usuario::class))
            ->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, $data));
    }

    public function test_update_partial_falls_back_to_existing_fields(): void
    {
        $existing = $this->makeUsuario();
        $updated  = $this->makeUsuario();
        $this->repo->method('find')->with(1)->willReturn($existing);
        $this->repo->expects($this->once())->method('update')
            ->with(1, $this->callback(function (Usuario $u) use ($existing) {
                return $u->getNome()  === 'Novo Nome'
                    && $u->getEmail() === $existing->getEmail();
            }))
            ->willReturn($updated);
        $this->assertSame($updated, $this->service->update(1, ['nome' => 'Novo Nome']));
    }

    public function test_store_throws_unprocessable_for_invalid_email(): void
    {
        $this->expectException(UnprocessableEntity::class);
        $this->service->store(['nome' => 'João', 'email' => 'not-an-email']);
    }

    public function test_update_throws_unprocessable_for_invalid_email(): void
    {
        $existing = $this->makeUsuario();
        $this->repo->method('find')->with(1)->willReturn($existing);
        $this->expectException(UnprocessableEntity::class);
        $this->service->update(1, ['email' => 'not-an-email']);
    }

    public function test_destroy_delegates_to_repo(): void
    {
        $this->repo->expects($this->once())->method('destroy')->with(1);
        $this->service->destroy(1);
    }

    public function test_store_logs_info_on_success(): void
    {
        $usuario = $this->makeUsuario(8);
        $this->repo->method('store')->willReturn($usuario);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('UsuarioService::store usuarioId=8'));
        $this->service->store(['nome' => 'João', 'email' => 'joao@test.com']);
    }

    public function test_store_logs_error_on_invalid_email(): void
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('UsuarioService::store FAILED:'));
        $this->expectException(UnprocessableEntity::class);
        $this->service->store(['nome' => 'João', 'email' => 'not-an-email']);
    }

    public function test_update_logs_info_on_success(): void
    {
        $existing = $this->makeUsuario(4);
        $updated  = $this->makeUsuario(4);
        $this->repo->method('find')->willReturn($existing);
        $this->repo->method('update')->willReturn($updated);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('UsuarioService::update usuarioId=4'));
        $this->service->update(4, ['nome' => 'Maria']);
    }

    public function test_update_logs_error_on_invalid_email(): void
    {
        $existing = $this->makeUsuario(2);
        $this->repo->method('find')->willReturn($existing);
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('UsuarioService::update FAILED:'));
        $this->expectException(UnprocessableEntity::class);
        $this->service->update(2, ['email' => 'not-an-email']);
    }

    public function test_destroy_logs_info_on_success(): void
    {
        $this->repo->method('destroy')->with(3);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('UsuarioService::destroy usuarioId=3'));
        $this->service->destroy(3);
    }
}
```

- [ ] **Step 2: Run tests to confirm failures**

```bash
./vendor/bin/phpunit tests/Services/UsuarioServiceTest.php --no-coverage
```

Expected: Failures — `UsuarioService` does not accept a second argument.

- [ ] **Step 3: Update UsuarioService to inject and use logger**

Replace the full content of `app/Services/UsuarioService.php`:

```php
<?php

namespace App\Services;

use App\Contracts\Repositories\UsuarioRepositoryInterface;
use App\Contracts\Services\UsuarioServiceInterface;
use App\Entities\Usuario;
use App\Exceptions\Http\UnprocessableEntity;
use App\ValueObjects\Email;
use Psr\Log\LoggerInterface;

class UsuarioService implements UsuarioServiceInterface
{
    public function __construct(
        private UsuarioRepositoryInterface $repo,
        private LoggerInterface $logger
    ) {
    }

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
        try {
            $usuario = new Usuario($data['nome'], new Email($data['email']));
        } catch (\InvalidArgumentException $e) {
            $this->logger->error("UsuarioService::store FAILED: {$e->getMessage()}");
            throw new UnprocessableEntity($e->getMessage());
        }

        $result = $this->repo->store($usuario);
        $this->logger->info("UsuarioService::store usuarioId={$result->getId()}");
        return $result;
    }

    public function update(int $id, mixed $data): Usuario
    {
        $existing = $this->repo->find($id);

        try {
            $email = isset($data['email']) ? new Email($data['email']) : $existing->getEmail();
        } catch (\InvalidArgumentException $e) {
            $this->logger->error("UsuarioService::update FAILED: {$e->getMessage()}");
            throw new UnprocessableEntity($e->getMessage());
        }

        $usuario = new Usuario(
            $data['nome'] ?? $existing->getNome(),
            $email
        );

        $result = $this->repo->update($id, $usuario);
        $this->logger->info("UsuarioService::update usuarioId={$id}");
        return $result;
    }

    public function destroy(int $id): void
    {
        $this->repo->destroy($id);
        $this->logger->info("UsuarioService::destroy usuarioId={$id}");
    }
}
```

- [ ] **Step 4: Run tests to confirm all pass**

```bash
./vendor/bin/phpunit tests/Services/UsuarioServiceTest.php --no-coverage
```

Expected: All tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Services/UsuarioService.php tests/Services/UsuarioServiceTest.php
git commit -m "feat: inject logger into UsuarioService and log write operations and exceptions"
```

---

## Task 6: AppServiceProvider — Wire Logger into Service Bindings

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Run the full test suite to check current state before changes**

```bash
./vendor/bin/phpunit --no-coverage
```

Note any failures — after Tasks 1-5 the only failures should be in `AppServiceProviderTest` if it tests container resolution.

- [ ] **Step 2: Update AppServiceProvider to pass logger to all four services**

In `app/Providers/AppServiceProvider.php`, replace the four service `bind` calls (lines 103–128) with:

```php
        $this->container->bind(
            CursoServiceInterface::class,
            fn(Container $c) => new CursoService(
                $c->get(CursoRepositoryInterface::class),
                $c->get(LoggerInterface::class)
            )
        );

        $this->container->bind(
            TurmaServiceInterface::class,
            fn(Container $c) => new TurmaService(
                $c->get(TurmaRepositoryInterface::class),
                $c->get(CursoRepositoryInterface::class),
                $c->get(MatriculaRepositoryInterface::class),
                $c->get(LoggerInterface::class)
            )
        );

        $this->container->bind(
            UsuarioServiceInterface::class,
            fn(Container $c) => new UsuarioService(
                $c->get(UsuarioRepositoryInterface::class),
                $c->get(LoggerInterface::class)
            )
        );

        $this->container->bind(
            MatriculaServiceInterface::class,
            fn(Container $c) => new MatriculaService(
                $c->get(MatriculaRepositoryInterface::class),
                $c->get(TurmaRepositoryInterface::class),
                $c->get(LoggerInterface::class)
            )
        );
```

- [ ] **Step 3: Run the full test suite to confirm all tests pass**

```bash
./vendor/bin/phpunit --no-coverage
```

Expected: All tests green.

- [ ] **Step 4: Commit**

```bash
git add app/Providers/AppServiceProvider.php
git commit -m "feat: wire LoggerInterface into all service bindings in AppServiceProvider"
```
