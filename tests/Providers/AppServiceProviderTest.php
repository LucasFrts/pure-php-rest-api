<?php

namespace Tests\Providers;

use App\Contracts\ConfigInterface;
use App\Providers\AppServiceProvider;
use App\Support\Container;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AppServiceProviderTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function test_register_binds_config_interface(): void
    {
        (new AppServiceProvider($this->container))->register();
        $this->assertTrue($this->container->has(ConfigInterface::class));
    }

    public function test_register_binds_logger_interface(): void
    {
        (new AppServiceProvider($this->container))->register();
        $this->assertTrue($this->container->has(LoggerInterface::class));
    }

    public function test_config_resolves_to_config_instance(): void
    {
        (new AppServiceProvider($this->container))->register();
        $config = $this->container->get(ConfigInterface::class);
        $this->assertInstanceOf(ConfigInterface::class, $config);
    }

    public function test_config_is_singleton(): void
    {
        (new AppServiceProvider($this->container))->register();
        $first  = $this->container->get(ConfigInterface::class);
        $second = $this->container->get(ConfigInterface::class);
        $this->assertSame($first, $second);
    }

    public function test_logger_is_singleton(): void
    {
        (new AppServiceProvider($this->container))->register();
        $first  = $this->container->get(LoggerInterface::class);
        $second = $this->container->get(LoggerInterface::class);
        $this->assertSame($first, $second);
    }

    public function test_register_binds_exception_handler(): void
    {
        (new AppServiceProvider($this->container))->register();
        $this->assertTrue($this->container->has(\App\Support\ExceptionHandler::class));
    }
}
