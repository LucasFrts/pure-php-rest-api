<?php

namespace App\Support;

/**
 * Classe base para Service Providers da aplicação.
 *
 * Um Service Provider é responsável por registrar as ligações (bindings)
 * de interfaces e classes concretas no container de dependências. Cada
 * provider agrupa registros relacionados, mantendo o bootstrap organizado.
 *
 * Para criar um novo provider, basta estender esta classe e implementar
 * o método register() com os bindings desejados.
 */
abstract class ServiceProvider
{
    /**
     * @param Container $container Container de dependências da aplicação.
     */
    public function __construct(protected Container $container)
    {
    }

    /**
     * Registra os bindings de dependências no container.
     *
     * Este método é chamado automaticamente durante o bootstrap da aplicação.
     * Implemente-o no provider concreto para registrar singletons e fábricas.
     */
    abstract public function register(): void;
}
