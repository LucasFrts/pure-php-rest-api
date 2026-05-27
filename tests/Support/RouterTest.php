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
        $this->router->get('/turmas', [\stdClass::class, 'index']);

        $route = $this->router->route('/turmas', 'GET');

        $this->assertSame('/turmas', $route['uri']);
        $this->assertSame('GET', $route['method']);
        $this->assertSame(\stdClass::class, $route['controller']);
        $this->assertSame('index', $route['action']);
    }

    public function test_prefix_prepends_to_uri(): void
    {
        $this->router->prefix('/api')->get('/turmas', [\stdClass::class, 'index']);

        $route = $this->router->route('/api/turmas', 'GET');

        $this->assertSame('/api/turmas', $route['uri']);
    }

    public function test_prefix_with_trailing_slash_does_not_produce_double_slash(): void
    {
        $this->router->prefix('/api/')->get('/turmas', [\stdClass::class, 'index']);

        $route = $this->router->route('/api/turmas', 'GET');

        $this->assertSame('/api/turmas', $route['uri']);
    }

    public function test_prefix_is_cleared_after_route_registration(): void
    {
        $this->router->prefix('/api')->get('/turmas', [\stdClass::class, 'index']);
        $this->router->get('/outros', [\stdClass::class, 'index']);

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
        $this->router->get('/turmas', [\stdClass::class, 'index']);

        $this->expectException(RouteNotFound::class);

        $this->router->route('/turmas', 'POST');
    }

    public function test_route_extracts_single_dynamic_param(): void
    {
        $router = new Router();
        $router->get('/items/{id}', [\stdClass::class, 'show']);
        $route = $router->route('/items/42', 'GET');
        $this->assertSame(['id' => '42'], $route['params']);
    }

    public function test_route_extracts_multiple_dynamic_params(): void
    {
        $router = new Router();
        $router->get('/a/{foo}/b/{bar}', [\stdClass::class, 'show']);
        $route = $router->route('/a/1/b/2', 'GET');
        $this->assertSame(['foo' => '1', 'bar' => '2'], $route['params']);
    }

    public function test_route_static_returns_empty_params(): void
    {
        $router = new Router();
        $router->get('/items', [\stdClass::class, 'index']);
        $route = $router->route('/items', 'GET');
        $this->assertSame([], $route['params']);
    }

    public function test_route_does_not_match_wrong_segment_count(): void
    {
        $router = new Router();
        $router->get('/items/{id}', [\stdClass::class, 'show']);
        $this->expectException(\App\Exceptions\Http\RouteNotFound::class);
        $router->route('/items/42/extra', 'GET');
    }
}
