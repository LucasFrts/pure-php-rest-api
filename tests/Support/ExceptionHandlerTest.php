<?php

namespace Tests\Support;

use App\Contracts\ConfigInterface;
use App\Contracts\HttpExceptionInterface;
use App\Contracts\RuntimeExceptionInterface;
use App\Exceptions\Http\InternalServerError;
use App\Exceptions\Http\NotFound;
use App\Support\Config;
use App\Support\ExceptionHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExceptionHandlerTest extends TestCase
{
    private function makeConfig(string $env): ConfigInterface
    {
        return new Config(['APP_ENV' => $env]);
    }

    private function captureOutput(callable $fn): string
    {
        ob_start();
        $fn();
        return ob_get_clean();
    }

    public function test_http_exception_exposes_message_in_production(): void
    {
        $handler = new ExceptionHandler($this->makeConfig('production'));
        $output = $this->captureOutput(fn() => $handler->handle(new NotFound('Custom not found')));
        $decoded = json_decode($output, true);

        $this->assertSame(404, $decoded['status']);
        $this->assertSame('Custom not found', $decoded['detail']);
    }

    public function test_runtime_exception_hides_message_in_production(): void
    {
        $handler = new ExceptionHandler($this->makeConfig('production'));
        $output = $this->captureOutput(fn() => $handler->handle(new InternalServerError('secret error')));
        $decoded = json_decode($output, true);

        $this->assertSame(500, $decoded['status']);
        $this->assertSame('Internal Server Error', $decoded['detail']);
    }

    public function test_runtime_exception_exposes_message_in_local(): void
    {
        $handler = new ExceptionHandler($this->makeConfig('local'));
        $output = $this->captureOutput(fn() => $handler->handle(new InternalServerError('secret error')));
        $decoded = json_decode($output, true);

        $this->assertSame(500, $decoded['status']);
        $this->assertSame('secret error', $decoded['detail']);
    }

    public function test_generic_throwable_hides_message_in_production(): void
    {
        $handler = new ExceptionHandler($this->makeConfig('production'));
        $output = $this->captureOutput(fn() => $handler->handle(new RuntimeException('db connection failed')));
        $decoded = json_decode($output, true);

        $this->assertSame(500, $decoded['status']);
        $this->assertSame('Internal Server Error', $decoded['detail']);
    }

    public function test_generic_throwable_exposes_message_in_local(): void
    {
        $handler = new ExceptionHandler($this->makeConfig('local'));
        $output = $this->captureOutput(fn() => $handler->handle(new RuntimeException('db connection failed')));
        $decoded = json_decode($output, true);

        $this->assertSame(500, $decoded['status']);
        $this->assertSame('db connection failed', $decoded['detail']);
    }

    public function test_response_has_rfc7807_structure(): void
    {
        $handler = new ExceptionHandler($this->makeConfig('production'));
        $output = $this->captureOutput(fn() => $handler->handle(new NotFound()));
        $decoded = json_decode($output, true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertArrayHasKey('title', $decoded);
        $this->assertArrayHasKey('detail', $decoded);
    }

    public function test_title_matches_status_code(): void
    {
        $handler = new ExceptionHandler($this->makeConfig('production'));
        $output = $this->captureOutput(fn() => $handler->handle(new NotFound()));
        $decoded = json_decode($output, true);

        $this->assertSame('Not Found', $decoded['title']);
    }
}
