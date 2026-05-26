<?php

namespace Tests\Support;

use App\Support\Container;
use App\Support\Dispatcher;
use PHPUnit\Framework\TestCase;

class FakeController
{
    public static bool $called = false;
    public static string $lastAction = '';

    public function index(): void
    {
        self::$called     = true;
        self::$lastAction = 'index';
    }

    public function show(): void
    {
        self::$called     = true;
        self::$lastAction = 'show';
    }
}

class DispatcherTest extends TestCase
{
    private Container  $container;
    private Dispatcher $dispatcher;

    protected function setUp(): void
    {
        FakeController::$called     = false;
        FakeController::$lastAction = '';

        $this->container  = new Container();
        $this->dispatcher = new Dispatcher($this->container, 'Tests\\Support\\');
    }

    public function test_dispatch_resolves_and_calls_action(): void
    {
        $route = [
            'controller' => 'FakeController',
            'action'     => 'index',
        ];

        $this->dispatcher->dispatch($route);

        $this->assertTrue(FakeController::$called);
        $this->assertSame('index', FakeController::$lastAction);
    }

    public function test_dispatch_calls_correct_action(): void
    {
        $route = [
            'controller' => 'FakeController',
            'action'     => 'show',
        ];

        $this->dispatcher->dispatch($route);

        $this->assertSame('show', FakeController::$lastAction);
    }
}
