<?php

namespace App\Support;

use App\Contracts\ResponseInterface;

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
     * O controller é obtido via container, garantindo que suas dependências
     * (como Request e Response) sejam injetadas automaticamente. Após a execução,
     * se o retorno for uma ResponseInterface, o método send() é chamado para
     * emitir a resposta HTTP.
     *
     * @param array{controller: string, action: string} $route Rota resolvida pelo Router.
     *
     * @throws \RuntimeException Quando o método de action não existe no controller.
     */
    public function dispatch(array $route): void
    {
        $fullClassWithNamespace = $this->controllerNamespace . $route['controller'];
        $action = $route['action'];

        $controller = $this->container->get($fullClassWithNamespace);

        if (!method_exists($controller, $action)) {
            throw new \RuntimeException("Action '{$action}' not found on controller '{$fullClassWithNamespace}'.");
        }

        $result = $controller->$action();

        if ($result instanceof ResponseInterface) {
            $result->send();
        }
    }
}
