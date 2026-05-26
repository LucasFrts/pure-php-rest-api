<?php

namespace App\Support;

use App\Exceptions\Http\NotFound;
use App\Exceptions\Http\RouteNotFound;

/**
 * Gerencia o registro e a resolução de rotas HTTP da aplicação.
 *
 * Cada rota associa um par (URI, método HTTP) a um controller e action no
 * formato 'NomeController@nomeMetodo'. Os métodos de registro (get, post, etc.)
 * retornam a própria instância para permitir encadeamento de chamadas no
 * arquivo de rotas.
 *
 * A resolução é feita por correspondência exata de URI e método. Rotas com
 * parâmetros dinâmicos não são suportadas nesta implementação.
 */
class Router
{
    /** @var array<int, array{uri: string, controller: string, method: string, action: string}> */
    protected array $routes = [];
    protected string|null $prefix = null;

    /**
     * Registra uma rota para requisições GET.
     *
     * @param string $uri    URI exata (ex: '/users').
     * @param string $action Controller e método no formato 'Controller@metodo'.
     */
    public function get(string $uri, string $action): self
    {
        $this->add($uri, $action, 'GET');
        return $this;
    }

    /**
     * Registra uma rota para requisições POST.
     *
     * @param string $uri    URI exata (ex: '/users').
     * @param string $action Controller e método no formato 'Controller@metodo'.
     */
    public function post(string $uri, string $action): self
    {
        $this->add($uri, $action, 'POST');
        return $this;
    }

    /**
     * Registra uma rota para requisições PUT.
     *
     * @param string $uri    URI exata (ex: '/users/1').
     * @param string $action Controller e método no formato 'Controller@metodo'.
     */
    public function put(string $uri, string $action): self
    {
        $this->add($uri, $action, 'PUT');
        return $this;
    }

    /**
     * Registra uma rota para requisições PATCH.
     *
     * @param string $uri    URI exata (ex: '/users/1').
     * @param string $action Controller e método no formato 'Controller@metodo'.
     */
    public function patch(string $uri, string $action): self
    {
        $this->add($uri, $action, 'PATCH');
        return $this;
    }

    /**
     * Registra uma rota para requisições DELETE.
     *
     * @param string $uri    URI exata (ex: '/users/1').
     * @param string $action Controller e método no formato 'Controller@metodo'.
     */
    public function delete(string $uri, string $action): self
    {
        $this->add($uri, $action, 'DELETE');
        return $this;
    }

    /**
     * Localiza e retorna a rota que corresponde à URI e método informados.
     *
     * Lança RouteNotFound se nenhuma rota cadastrada corresponder ao par URI + método,
     * o que resulta em uma resposta 404 tratada pelo ExceptionHandler.
     *
     * @param string $uri    URI da requisição atual.
     * @param string $method Método HTTP da requisição (case-insensitive).
     *
     * @return array{uri: string, controller: string, method: string, action: string}
     *
     * @throws RouteNotFound Quando nenhuma rota corresponde à requisição.
     */
    public function route(string $uri, string $method): array
    {
        foreach ($this->routes as $route) {
            if ($route['uri'] === $uri && $route['method'] === strtoupper($method)) {
                return $route;
            }
        }

        throw new RouteNotFound();
    }

    public function prefix(string $prefix) : self
    {
        if($this->hasPrefix()){
            $this->prefix .= "/{$prefix}";
            return $this;
        }

        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Armazena internamente a rota decompondo a action em controller e método.
     *
     * @param string $uri    URI da rota.
     * @param string $action String no formato 'Controller@metodo'.
     * @param string $method Método HTTP em maiúsculas.
     */
    private function add(string $uri, string $action, string $method): void
    {
        $uri = $this->withPrefix($uri);
        [$controller, $controllerMethod] = explode('@', $action);
        $this->routes[] = [
            'uri'        => $uri,
            'controller' => $controller,
            'method'     => $method,
            'action'     => $controllerMethod,
        ];
    }

    private function hasPrefix() : bool
    {
        return !empty($this->prefix);
    }

    private function withPrefix(string $uri) : string
    {
        if($this->hasPrefix()){
            $uri = rtrim($this->prefix, '/') . '/' . ltrim($uri, '/');
            $this->clearPrefix();
        }

        return $uri;
    }

    private function clearPrefix() : void
    {
        $this->prefix = null;
    }
}
