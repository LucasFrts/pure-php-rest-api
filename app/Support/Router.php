<?php

namespace App\Support;

use App\Exceptions\Http\NotFound;

class Router
{
    protected array $routes = [];

    public function get(string $uri, string $action)
    {
        $this->add($uri, $action, 'GET');

        return $this;
    }

    public function post(string $uri, string $action)
    {
        $this->add($uri, $action, 'POST');

        return $this;
    }

    public function put(string $uri, string $action)
    {
        $this->add($uri, $action, 'PUT');

        return $this;
    }

    public function patch(string $uri, string $action)
    {
        $this->add($uri, $action, 'PATCH');
        
        return $this;
    }

    public function delete(string $uri, string $action)
    {
        $this->add($uri, $action, 'DELETE');

        return $this;
    }

    public function route(string $uri, string $method, string|null $query = null)
    {
        foreach($this->routes as $route){
            if ($route['uri'] === $uri && $route['method'] === strtoupper($method)) return $route;
        }

        throw new NotFound();
    }

    private function add(string $uri, string $action, string $method)
    {
        [$controller, $controllerMethod] = explode('@', $action);
        $this->routes[] = [
            'uri' => $uri,
            'controller' => $controller,
            'method' => $method,
            'action' => $controllerMethod
        ];
    }
}