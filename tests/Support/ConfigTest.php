<?php

namespace Tests\Support;

use App\Contracts\ConfigInterface;
use App\Support\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function test_implements_config_interface(): void
    {
        $config = new Config([]);
        $this->assertInstanceOf(ConfigInterface::class, $config);
    }

    public function test_get_returns_value_for_existing_key(): void
    {
        $config = new Config(['APP_NAME' => 'dot-group']);
        $this->assertSame('dot-group', $config->get('APP_NAME'));
    }

    public function test_get_returns_default_for_missing_key(): void
    {
        $config = new Config([]);
        $this->assertSame('fallback', $config->get('MISSING', 'fallback'));
    }

    public function test_get_returns_null_when_key_missing_and_no_default(): void
    {
        $config = new Config([]);
        $this->assertNull($config->get('MISSING'));
    }

    public function test_get_does_not_return_default_when_value_is_zero(): void
    {
        $config = new Config(['TIMEOUT' => 0]);
        $this->assertSame(0, $config->get('TIMEOUT', 30));
    }
}
