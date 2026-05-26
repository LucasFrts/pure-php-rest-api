<?php

namespace App\Support;

use App\Contracts\ConfigInterface;

/**
 * Implementação simples de configuração baseada em array.
 *
 * Recebe as configurações como array puro no construtor — normalmente
 * carregado a partir de um arquivo PHP que retorna um array (config.php).
 * Isso mantém as configurações isoladas de superglobais e fáceis de testar.
 */
class Config implements ConfigInterface
{
    /**
     * @param array<string, mixed> $data Mapa de chave => valor das configurações.
     */
    public function __construct(private array $data)
    {
    }

    /**
     * Retorna o valor de uma configuração pelo nome da chave.
     *
     * Usa array_key_exists para distinguir chaves ausentes de chaves com valor null,
     * garantindo que configurações explicitamente definidas como null sejam retornadas
     * corretamente em vez de cair no valor padrão.
     *
     * @param string $key     Nome da configuração (ex: 'APP_ENV', 'LOG_PATH').
     * @param mixed  $default Valor retornado quando a chave não for encontrada.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }
}
