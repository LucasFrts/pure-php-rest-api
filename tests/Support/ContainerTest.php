<?php

namespace Tests\Support;

use App\Support\Container;
use PHPUnit\Framework\TestCase;

class SimpleService {}

class ServiceWithDep
{
    public function __construct(public SimpleService $dep) {}
}

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function test_bind_resolves_closure(): void
    {
        $this->container->bind('greeting', fn() => 'hello');
        $this->assertSame('hello', $this->container->get('greeting'));
    }

    public function test_bind_passes_container_to_closure(): void
    {
        $this->container->bind(SimpleService::class, fn() => new SimpleService());
        $this->container->bind('uses_container', fn(Container $c) => $c->get(SimpleService::class));

        $result = $this->container->get('uses_container');
        $this->assertInstanceOf(SimpleService::class, $result);
    }

    public function test_singleton_returns_same_instance(): void
    {
        $this->container->singleton(SimpleService::class, fn() => new SimpleService());

        $first  = $this->container->get(SimpleService::class);
        $second = $this->container->get(SimpleService::class);

        $this->assertSame($first, $second);
    }

    public function test_bind_returns_new_instance_each_time(): void
    {
        $this->container->bind(SimpleService::class, fn() => new SimpleService());

        $first  = $this->container->get(SimpleService::class);
        $second = $this->container->get(SimpleService::class);

        $this->assertNotSame($first, $second);
    }

    public function test_auto_resolve_no_constructor(): void
    {
        $result = $this->container->get(SimpleService::class);
        $this->assertInstanceOf(SimpleService::class, $result);
    }

    public function test_auto_resolve_injects_dependencies(): void
    {
        $result = $this->container->get(ServiceWithDep::class);
        $this->assertInstanceOf(ServiceWithDep::class, $result);
        $this->assertInstanceOf(SimpleService::class, $result->dep);
    }

    public function test_has_returns_true_after_bind(): void
    {
        $this->container->bind('foo', fn() => 'bar');
        $this->assertTrue($this->container->has('foo'));
    }

    public function test_has_returns_false_for_unknown_id(): void
    {
        $this->assertFalse($this->container->has('unknown'));
    }
}
