<?php

namespace App\Support;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;

/**
 * Container de injeção de dependências com autowiring via Reflection.
 *
 * Implementa PSR-11 (ContainerInterface) e resolve dependências automaticamente
 * a partir dos type hints dos construtores. Suporta dois modos de registro:
 *
 * - bind: cria uma nova instância a cada chamada de get().
 * - singleton: cria a instância apenas na primeira chamada e reutiliza nas demais.
 *
 * Quando uma classe não está registrada, o container tenta instanciá-la
 * diretamente via Reflection, resolvendo suas dependências recursivamente.
 */
class Container implements ContainerInterface
{
    /** Instância singleton do próprio container, usada como ponto de acesso global. */
    protected static ?Container $instance = null;

    /** @var array<string, Closure|ReflectionClass> Fábricas e reflexões registradas. */
    protected array $services = [];

    /** @var array<string, true> Identificadores marcados como singleton. */
    protected array $singletons = [];

    /** @var array<string, mixed> Cache de instâncias singleton já resolvidas. */
    protected array $instances = [];

    /**
     * Resolve e retorna a instância do serviço identificado por $id.
     *
     * Se o serviço for singleton e já tiver sido resolvido, retorna o cache.
     * Caso contrário, chama make() para criar a instância.
     *
     * @param string $id Nome da classe ou interface registrada.
     */
    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $instance = $this->make($id);

        if (isset($this->singletons[$id])) {
            $this->instances[$id] = $instance;
        }

        return $instance;
    }

    /**
     * Verifica se um serviço está registrado no container.
     *
     * @param string $id Nome da classe ou interface.
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }

    /**
     * Registra uma fábrica que cria uma nova instância a cada chamada de get().
     *
     * @param string  $id      Nome da classe ou interface.
     * @param Closure $factory Função que recebe o container e retorna a instância.
     */
    public function bind(string $id, Closure $factory): void
    {
        $this->services[$id] = $factory;
    }

    /**
     * Registra uma fábrica que cria a instância apenas uma vez e a reutiliza.
     *
     * Ideal para serviços que precisam ser compartilhados (logger, config, etc.).
     *
     * @param string  $id      Nome da classe ou interface.
     * @param Closure $factory Função que recebe o container e retorna a instância.
     */
    public function singleton(string $id, Closure $factory): void
    {
        $this->services[$id]   = $factory;
        $this->singletons[$id] = true;
    }

    /**
     * Registra uma classe para ser resolvida via Reflection sem fábrica explícita.
     *
     * @param string      $id     Nome pelo qual o serviço será recuperado.
     * @param string|null $target Classe concreta a instanciar; usa $id se omitido.
     */
    public function register(string $id, ?string $target = null): void
    {
        $target = $target ?? $id;
        $this->services[$id] = new ReflectionClass($target);
    }

    /**
     * Cria a instância de um serviço, seja via fábrica registrada ou via autowiring.
     *
     * Se o serviço não estiver registrado, tenta resolver a classe diretamente
     * pelo nome completo (FQCN) usando Reflection.
     *
     * @param string $id Nome da classe ou interface.
     */
    private function make(string $id): mixed
    {
        if ($this->has($id)) {
            $service = $this->services[$id];

            if ($service instanceof Closure) {
                return $service($this);
            }

            return $this->getInstance($service);
        }

        $reflection = new ReflectionClass($id);
        $this->services[$id] = $reflection;
        return $this->getInstance($reflection);
    }

    /**
     * Instancia uma classe resolvendo suas dependências via Reflection.
     *
     * Lê os parâmetros do construtor e chama get() recursivamente para cada
     * dependência tipada. Lança uma exceção se encontrar um parâmetro sem type hint,
     * pois não é possível resolver dependências sem tipo declarado.
     *
     * @param ReflectionClass $service Reflexão da classe a ser instanciada.
     *
     * @throws \RuntimeException Quando um parâmetro do construtor não tem type hint.
     */
    private function getInstance(ReflectionClass $service): mixed
    {
        $constructor = $service->getConstructor();

        if (is_null($constructor) || $constructor->getNumberOfParameters() === 0) {
            return $service->newInstance();
        }

        $params = [];
        foreach ($constructor->getParameters() as $parameter) {
            if ($paramType = $parameter->getType()) {
                $params[] = $this->get($paramType->getName());
            } else {
                throw new \RuntimeException(
                    "Cannot auto-resolve parameter \${$parameter->getName()} in {$service->getName()}: no type hint."
                );
            }
        }

        return $service->newInstanceArgs($params);
    }

    /**
     * Retorna a instância singleton do container, criando-a se necessário.
     *
     * Permite acesso ao container em pontos onde a injeção de dependência
     * não é viável, como no bootstrap da aplicação.
     */
    public static function getContainer(): Container
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Define uma nova instância singleton do container.
     *
     * Útil para testes ou substituição dinâmica da instância.
     *
     * @param Container $instance A nova instância do container.
     */
    public static function setContainer(Container $instance): void
    {
        static::$instance = $instance;
    }

    /**
     * Remove a instância singleton em cache para o identificador informado.
     *
     * Útil em testes para forçar a recriação de um singleton após re-registrar
     * sua fábrica, evitando que o cache stale seja retornado por get().
     *
     * @param string $id Nome da classe ou interface.
     */
    public function forgetInstance(string $id): void
    {
        unset($this->instances[$id]);
    }
}
