<?php

namespace App\Support;

use App\Contracts\ResponseInterface;
use RuntimeException;

/**
 * Responsável por despachar uma requisição para o controller e action corretos.
 *
 * Recebe a rota resolvida pelo Router, localiza a classe do controller no container
 * (garantindo que suas dependências sejam injetadas), chama o método correspondente
 * e, se o retorno implementar ResponseInterface, envia a resposta ao cliente.
 *
 * O namespace padrão dos controllers pode ser sobrescrito no construtor,
 * o que facilita testes e possíveis extensões futuras.
 */
class Dispatcher
{
    private const DEFAULT_NAMESPACE = 'App\\Http\\Controllers\\';

    /**
     * @param Container $container           Container de dependências para resolver o controller.
     * @param string    $controllerNamespace Namespace base onde os controllers estão localizados.
     */
    public function __construct(
        private Container $container,
        private string $controllerNamespace = self::DEFAULT_NAMESPACE
    )
    {
    }

    /**
     * Resolve e executa a action do controller indicado pela rota.
     *
     * Aceita tanto FQCN completo (quando registrado via Controller::class) quanto
     * nome curto (quando usado com controllerNamespace customizado, como em testes).
     * Os params extraídos pelo Router são repassados como args posicionais ao action.
     *
     * @param array{controller: string, action: string, params: array<string, string>} $route
     *
     * @throws \RuntimeException Quando o método de action não existe no controller.
     */
    public function dispatch(array $route): ?ResponseInterface
    {
        $fqcn = str_contains($route['controller'], '\\')
            ? $route['controller']
            : $this->controllerNamespace . $route['controller'];
        $action = $route['action'];

        $controller = $this->container->get($fqcn);

        if (!method_exists($controller, $action)) {
            throw new RuntimeException("Action '{$action}' not found on controller '{$fqcn}'.");
        }

        $result = $controller->$action(...array_values($route['params'] ?? []));

        return $result instanceof ResponseInterface ? $result : null;
    }
}
