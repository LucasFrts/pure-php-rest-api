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
     * @param mixed                $level   Nível do log (ex: 'info', 'error').
     * @param mixed                $message Mensagem a registrar.
     * @param array<string, mixed> $context Dados de contexto opcionais.
     */
    public function log($level, $message, array $context = []): void
    {
        $this->driver->log($level, $message, $context);
    }
}
