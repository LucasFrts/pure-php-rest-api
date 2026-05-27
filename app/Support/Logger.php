<?php

namespace App\Support;

use App\Contracts\ConfigInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as MonoLogger;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Wrapper de logger que encapsula o Monolog como driver interno.
 *
 * Estende AbstractLogger do PSR-3 para implementar apenas o método log(),
 * enquanto os demais métodos de conveniência (info, error, debug, etc.)
 * são herdados automaticamente. Isso desacopla o código da aplicação do
 * Monolog diretamente: se quisermos trocar o driver no futuro, basta
 * alterar esta classe sem tocar em nada que usa LoggerInterface.
 *
 * O caminho do arquivo de log é lido da configuração via LOG_PATH,
 * com fallback para storage/logs dentro do projeto. Um novo arquivo
 * é criado por dia (app-YYYY-MM-DD.log) via RotatingFileHandler.
 */
class Logger extends AbstractLogger
{
    /** Driver concreto de log (Monolog) que processa as mensagens. */
    private LoggerInterface $driver;

    /**
     * @param ConfigInterface $config Configurações da aplicação, usadas para obter LOG_PATH.
     */
    public function __construct(ConfigInterface $config)
    {
        $logPath = $config->get('LOG_PATH', __DIR__ . '/../../storage/logs');

        $monolog = new MonoLogger('app');
        $monolog->pushHandler(new RotatingFileHandler("{$logPath}/app.log", maxFiles: 14));

        $this->driver = $monolog;
    }

    /**
     * Delega o registro da mensagem ao driver interno.
     *
     * Assinatura sem type hints em $level e $message para manter compatibilidade
     * com a interface PSR-3, que aceita mixed nesses parâmetros.
     *
     * @param mixed                $level   Nível do log (ex: 'info', 'error').
     * @param mixed                $message Mensagem a registrar.
     * @param array<string, mixed> $context Dados de contexto opcionais.
     */
    public function log($level, $message, array $context = []): void
    {
        $this->driver->log($level, $message, $context);
    }
}
