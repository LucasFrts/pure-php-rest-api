<?php

namespace App\Contracts;

/**
 * Contrato para acesso às configurações da aplicação.
 *
 * Define como qualquer implementação de configuração deve se comportar,
 * permitindo trocar a fonte dos dados (arquivo PHP, .env, banco) sem
 * alterar o código que consome as configurações.
 */
interface ConfigInterface
{
    /**
     * Retorna o valor de uma configuração pelo nome da chave.
     *
     * Caso a chave não exista, retorna o valor padrão informado.
     *
     * @param string $key     Nome da configuração (ex: 'APP_ENV', 'LOG_PATH').
     * @param mixed  $default Valor retornado quando a chave não for encontrada.
     */
    public function get(string $key, mixed $default = null): mixed;
}
