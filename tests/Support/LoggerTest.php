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
