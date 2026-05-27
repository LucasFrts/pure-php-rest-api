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
    protected string|null $permanentPrefix = null;

    /**
     * Registra uma rota para requisições GET.
     *
     * @param string $uri    URI exata (ex: '/users').
     * @param array $action Controller e método no formato '[Controlller::cass, método]'.
     */
    public function get(string $uri, array $action): self
    {
        $this->add($uri, $action, 'GET');
        return $this;
    }

    /**
     * Registra uma rota para requisições POST.
     *
     * @param string $uri    URI exata (ex: '/users').
     * @param array $action Controller e método no formato '[Controlller::cass, método]'.
     */
    public function post(string $uri, array $action): self
    {
        $this->add($uri, $action, 'POST');
        return $this;
    }

    /**
     * Registra uma rota para requisições PUT.
     *
     * @param string $uri    URI exata (ex: '/users/1').
     * @param array $action Controller e método no formato '[Controlller::cass, método]'.
     */
    public function put(string $uri, array $action): self
    {
        $this->add($uri, $action, 'PUT');
        return $this;
    }

    /**
     * Registra uma rota para requisições PATCH.
     *
     * @param string $uri    URI exata (ex: '/users/1').
     * @param array $action Controller e método no formato '[Controlller::cass, método]'.
     */
    public function patch(string $uri, array $action): self
    {
        $this->add($uri, $action, 'PATCH');
        return $this;
    }

    /**
     * Registra uma rota para requisições DELETE.
     *
     * @param string $uri    URI exata (ex: '/users/1').
     * @param array $action Controller e método no formato '[Controlller::cass, método]'.
     */
    public function delete(string $uri, array $action): self
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
            $pattern = $this->toRegex($route['uri']);
            if (preg_match($pattern, $uri, $matches) && $route['method'] === strtoupper($method)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return array_merge($route, ['params' => $params]);
            }
        }

        throw new RouteNotFound();
    }

    private function toRegex(string $uri): string
    {
        $parts = preg_split('/(\{[a-zA-Z_][a-zA-Z0-9_]*\})/', $uri, -1, PREG_SPLIT_DELIM_CAPTURE);
        $pattern = '';
        foreach ($parts as $part) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $part, $m)) {
                $pattern .= '(?P<' . $m[1] . '>[^/]+)';
            } else {
                $pattern .= preg_quote($part, '#');
            }
        }
        return '#^' . $pattern . '$#';
    }

    public function prefix(string $prefix) : self
    {
        if ($this->permanentPrefix !== null) {
            $this->permanentPrefix = rtrim($this->permanentPrefix, '/') . '/' . ltrim($prefix, '/');
        } else {
            $this->permanentPrefix = $prefix;
        }

        $this->prefix = $this->permanentPrefix;
        return $this;
    }

    /**
     * Armazena internamente a rota decompondo a action em controller e método.
     *
     * @param string $uri    URI da rota.
     * @param array $action Controller e método no formato '[Controlller::cass, método]'.
     * @param string $method Método HTTP em maiúsculas.
     */
    private function add(string $uri, array $action, string $method): void
    {
        $uri = $this->withPrefix($uri);
        $this->prefix = $this->permanentPrefix;
        [$controller, $controllerAction] = $action;
        $this->routes[] = [
            'uri'        => $uri,
            'controller' => $controller,
            'method'     => $method,
            'action'     => $controllerAction,
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
        }

        return $uri;
    }
}
