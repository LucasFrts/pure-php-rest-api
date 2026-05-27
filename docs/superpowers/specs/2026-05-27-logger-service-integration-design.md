# Logger Service Integration — Design Spec

**Date:** 2026-05-27
**Status:** Approved

## Overview

Integrate the existing `Logger` class into all four application services for write-operation tracing and exception logging. Update the Logger to write one file per day via Monolog's `RotatingFileHandler`.

## Scope

- `app/Support/Logger.php` — switch to daily rotation
- `app/Services/{Curso,Turma,Matricula,Usuario}Service.php` — inject and use logger
- `app/Providers/AppServiceProvider.php` — wire logger into service bindings
- `tests/Support/LoggerTest.php` — update for rotating file name
- `tests/Services/*ServiceTest.php` — add logging assertion cases

## 1. Logger — Daily Rotation

Replace `StreamHandler` with `RotatingFileHandler`. Base filename stays `app.log`; Monolog appends the date automatically, producing `app-YYYY-MM-DD.log`.

```php
use Monolog\Handler\RotatingFileHandler;

$monolog->pushHandler(new RotatingFileHandler("{$logPath}/app.log", maxFiles: 0));
```

`maxFiles: 0` disables automatic cleanup (retention policy deferred).

No other changes to `Logger.php`.

## 2. Services — Logger Injection

Each service receives `LoggerInterface` as a second constructor parameter.

### What gets logged

| Event | Level | Format |
|-------|-------|--------|
| Write operation success | `info` | `"ServiceName::method param=val → result"` |
| Exception caught (before rethrow) | `error` | `"ServiceName::method FAILED: {message}"` |

Read operations (`get`, `find`, `getByUsuario`) are **not** logged.

### Per-service methods

**CursoService**
- `store` → info `"CursoService::store cursoId={id}"`
- `update` → info `"CursoService::update cursoId={id}"`
- `destroy` → info `"CursoService::destroy cursoId={id}"`

**TurmaService**
- `store` → info `"TurmaService::store turmaId={id}"`
- `update` → info `"TurmaService::update turmaId={id}"`
- `destroy` → info `"TurmaService::destroy turmaId={id}"`

**MatriculaService**
- `enroll` → info `"MatriculaService::enroll usuarioId={x} turmaId={y} → matriculaId={id}"`
- `enroll` exception → error `"MatriculaService::enroll FAILED: {message}"` then rethrow
- `updateStatus` → info `"MatriculaService::updateStatus matriculaId={id} → {status}"`
- `updateStatus` exception → error `"MatriculaService::updateStatus FAILED: {message}"` then rethrow
- `destroy` → info `"MatriculaService::destroy matriculaId={id}"`

**UsuarioService**
- `store` → info `"UsuarioService::store usuarioId={id}"`
- `store` exception (`InvalidArgumentException`) → error `"UsuarioService::store FAILED: {message}"` then rethrow as `UnprocessableEntity`
- `update` → info `"UsuarioService::update usuarioId={id}"`
- `update` exception → error `"UsuarioService::update FAILED: {message}"` then rethrow as `UnprocessableEntity`
- `destroy` → info `"UsuarioService::destroy usuarioId={id}"`

### Exception pattern

```php
try {
    // ... operation
} catch (SomeException $e) {
    $this->logger->error('ServiceName::method FAILED: ' . $e->getMessage());
    throw $e; // or wrap as UnprocessableEntity where applicable
}
```

## 3. AppServiceProvider — Wiring

Each `bind` for a service interface passes `$c->get(LoggerInterface::class)` as the logger argument:

```php
$this->container->bind(
    CursoServiceInterface::class,
    fn(Container $c) => new CursoService(
        $c->get(CursoRepositoryInterface::class),
        $c->get(LoggerInterface::class)
    )
);
```

Same pattern for `TurmaService`, `MatriculaService`, `UsuarioService`.

## 4. Tests

### LoggerTest updates

- `tearDown`: replace `unlink('app.log')` with `glob("{$logPath}/app-*.log")` cleanup
- `test_writes_info_message_to_log_file`: assert file exists using today's date pattern `app-{date}.log`
- Add `test_writes_to_daily_rotating_file`: verifies filename contains current date

### ServiceTest additions (per service)

Each `setUp` adds a `LoggerInterface` mock. New test cases:

- `test_store_logs_info_on_success` — asserts `info` called once with string containing `"store"`
- `test_update_logs_info_on_success` — asserts `info` called once with string containing `"update"`
- `test_destroy_logs_info_on_success` — asserts `info` called once with string containing `"destroy"`

**MatriculaServiceTest additional cases:**
- `test_enroll_logs_info_on_success` — asserts message contains `"enroll"` and `"matriculaId"`
- `test_enroll_logs_error_on_turma_encerrada` — asserts `error` called, exception still thrown
- `test_enroll_logs_error_on_periodo_invalido` — asserts `error` called, exception still thrown
- `test_updateStatus_logs_error_on_invalid_status` — asserts `error` called, exception still thrown

**UsuarioServiceTest additional cases:**
- `test_store_logs_error_on_invalid_email` — asserts `error` called, `UnprocessableEntity` thrown

## 5. Files Changed

| File | Change |
|------|--------|
| `app/Support/Logger.php` | `RotatingFileHandler` |
| `app/Services/CursoService.php` | inject logger, log write ops |
| `app/Services/TurmaService.php` | inject logger, log write ops |
| `app/Services/MatriculaService.php` | inject logger, log write ops + exceptions |
| `app/Services/UsuarioService.php` | inject logger, log write ops + exceptions |
| `app/Providers/AppServiceProvider.php` | pass logger to service bindings |
| `tests/Support/LoggerTest.php` | update for rotating filenames |
| `tests/Services/CursoServiceTest.php` | add logging assertions |
| `tests/Services/TurmaServiceTest.php` | add logging assertions |
| `tests/Services/MatriculaServiceTest.php` | add logging assertions |
| `tests/Services/UsuarioServiceTest.php` | add logging assertions |
