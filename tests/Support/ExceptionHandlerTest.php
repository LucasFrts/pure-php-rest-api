<?php

namespace Tests\Support;

use App\Contracts\ConfigInterface;
use App\Contracts\HttpExceptionInterface;
use App\Contracts\RuntimeExceptionInterface;
use App\Exceptions\Http\InternalServerError;
use App\Exceptions\Http\NotFound;
use App\Exceptions\Http\UnprocessableEntity;
use App\Support\Config;
use App\Support\ExceptionHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

class ExceptionHandlerTest extends TestCase
{
    private function makeConfig(string $env): ConfigInterface
    {
        return new Config(['APP_ENV' => $env]);
    }

    private function makeHandler(string $env, ?LoggerInterface $logger = null): ExceptionHandler
    {
        return new ExceptionHandler($this->makeConfig($env), $logger ?? new NullLogger());
    }

    private function captureOutput(callable $fn): string
    {
        ob_start();
        $fn();
        return ob_get_clean();
    }

    public function test_http_exception_exposes_message_in_production(): void
    {
        $handler = $this->makeHandler('production');
        $output = $this->captureOutput(fn() => $handler->handle(new NotFound('Custom not found')));
        $decoded = json_decode($output, true);

        $this->assertSame(404, $decoded['status']);
        $this->assertSame('Custom not found', $decoded['detail']);
    }

    public function test_runtime_exception_hides_message_in_production(): void
    {
        $handler = $this->makeHandler('production');
        $output = $this->captureOutput(fn() => $handler->handle(new InternalServerError('secret error')));
        $decoded = json_decode($output, true);

        $this->assertSame(500, $decoded['status']);
        $this->assertSame('Internal Server Error', $decoded['detail']);
    }

    public function test_runtime_exception_exposes_message_in_local(): void
    {
        $handler = $this->makeHandler('local');
        $output = $this->captureOutput(fn() => $handler->handle(new InternalServerError('secret error')));
        $decoded = json_decode($output, true);

        $this->assertSame(500, $decoded['status']);
        $this->assertSame('secret error', $decoded['detail']);
    }

    public function test_generic_throwable_hides_message_in_production(): void
    {
        $handler = $this->makeHandler('production');
        $output = $this->captureOutput(fn() => $handler->handle(new RuntimeException('db connection failed')));
        $decoded = json_decode($output, true);

        $this->assertSame(500, $decoded['status']);
        $this->assertSame('Internal Server Error', $decoded['detail']);
    }

    public function test_generic_throwable_exposes_message_in_local(): void
    {
        $handler = $this->makeHandler('local');
        $output = $this->captureOutput(fn() => $handler->handle(new RuntimeException('db connection failed')));
        $decoded = json_decode($output, true);

        $this->assertSame(500, $decoded['status']);
        $this->assertSame('db connection failed', $decoded['detail']);
    }

    public function test_response_has_rfc7807_structure(): void
    {
        $handler = $this->makeHandler('production');
        $output = $this->captureOutput(fn() => $handler->handle(new NotFound()));
        $decoded = json_decode($output, true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertArrayHasKey('title', $decoded);
        $this->assertArrayHasKey('detail', $decoded);
    }

    public function test_unprocessable_entity_includes_errors_payload(): void
    {
        $handler = $this->makeHandler('production');
        $output = $this->captureOutput(fn() => $handler->handle(
            new UnprocessableEntity('Campos obrigatórios ausentes ou inválidos', ['missing' => ['url_imagem']])
        ));
        $decoded = json_decode($output, true);

        $this->assertSame(422, $decoded['status']);
        $this->assertSame(['missing' => ['url_imagem']], $decoded['errors']);
    }

    public function test_title_matches_status_code(): void
    {
        $handler = $this->makeHandler('production');
        $output = $this->captureOutput(fn() => $handler->handle(new NotFound()));
        $decoded = json_decode($output, true);

        $this->assertSame('Not Found', $decoded['title']);
    }

    public function test_logs_server_errors_at_error_level(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                'db connection failed',
                $this->callback(fn(array $context) => $context['status'] === 500
                    && $context['exception'] instanceof RuntimeException)
            );

        $handler = $this->makeHandler('production', $logger);
        $this->captureOutput(fn() => $handler->handle(new RuntimeException('db connection failed')));
    }

    public function test_logs_client_errors_at_warning_level(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with(
                'Campos obrigatórios ausentes ou inválidos',
                $this->callback(fn(array $context) => $context['status'] === 422
                    && $context['errors'] === ['missing' => ['url_imagem']])
            );

        $handler = $this->makeHandler('production', $logger);
        $this->captureOutput(fn() => $handler->handle(
            new UnprocessableEntity('Campos obrigatórios ausentes ou inválidos', ['missing' => ['url_imagem']])
        ));
    }
}
