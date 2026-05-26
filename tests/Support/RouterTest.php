<?php

namespace Tests\Support;

use App\Exceptions\Http\RouteNotFound;
use App\Support\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router;
    }

    public function test_registers_get_route(): void
    {
        $this->router->get('/turmas', 'TurmasController@index');

        $route = $this->router->route('/turmas', 'GET');

        $this->assertSame('/turmas', $route['uri']);
        $this->assertSame('GET', $route['method']);
        $this->assertSame('TurmasController', $route['controller']);
        $this->assertSame('index', $route['action']);
    }

    public function test_prefix_prepends_to_uri(): void
    {
        $this->router->prefix('/api')->get('/turmas', 'TurmasController@index');

        $route = $this->router->route('/api/turmas', 'GET');

        $this->assertSame('/api/turmas', $route['uri']);
    }

    public function test_prefix_with_trailing_slash_does_not_produce_double_slash(): void
    {
        $this->router->prefix('/api/')->get('/turmas', 'TurmasController@index');

        $route = $this->router->route('/api/turmas', 'GET');

        $this->assertSame('/api/turmas', $route['uri']);
    }

    public function test_prefix_is_cleared_after_route_registration(): void
    {
        $this->router->prefix('/api')->get('/turmas', 'TurmasController@index');
        $this->router->get('/outros', 'OutrosController@index');

        $route = $this->router->route('/outros', 'GET');

        $this->assertSame('/outros', $route['uri']);
    }

    public function test_throws_route_not_found_for_unknown_uri(): void
    {
        $this->expectException(RouteNotFound::class);

        $this->router->route('/nao-existe', 'GET');
    }

    public function test_throws_route_not_found_for_wrong_method(): void
    {
        $this->router->get('/turmas', 'TurmasController@index');

        $this->expectException(RouteNotFound::class);

        $this->router->route('/turmas', 'POST');
    }
}
