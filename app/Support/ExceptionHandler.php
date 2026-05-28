<?php

namespace App\Support;

use App\Contracts\ConfigInterface;
use App\Contracts\HttpExceptionInterface;
use App\Contracts\RuntimeExceptionInterface;
use App\Exceptions\Http\UnprocessableEntity;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Centraliza o tratamento de todas as exceções não capturadas da aplicação.
 */
class ExceptionHandler
{
    /**
     * @param ConfigInterface   $config Configurações da aplicação, usadas para verificar APP_ENV.
     * @param LoggerInterface   $logger Logger PSR-3 para registrar erros tratados pela API.
     */
    public function __construct(
        private ConfigInterface $config,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Processa a exceção e emite a resposta de erro adequada ao cliente.
     * 
     * @param Throwable $e Qualquer exceção ou erro lançado pela aplicação.
     */
    public function handle(Throwable $e): void
    {

        $status = $this->statusCode($e);
        $body = $this->getBody($e, $status);

        $this->logException($e, $status);

        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/problem+json');
        }

        echo json_encode($body);
    }

    private function getBody(Throwable $e, int $status) : array
    {
        $isLocal = $this->config->get('APP_ENV') === 'local';

        $body = [
            'status' => $status,
            'title'  => $this->titleFromStatus($status),
            'detail' => ($status === 500 && !$isLocal) ? 'Internal Server Error' : $e->getMessage()
        ];

        if ($e instanceof UnprocessableEntity && $e->getErrors() !== []) {
            $body['errors'] = $e->getErrors();
        }

        return $body;
    }

    private function statusCode(Throwable $e) : int
    {
        if ($e instanceof HttpExceptionInterface || $e instanceof UnprocessableEntity) {
            return $e->getStatusCode();
        }

        return 500;
    }

    private function logException(Throwable $e, int $status): void
    {
        $context = [
            'status'    => $status,
            'exception' => $e,
            'method'    => $_SERVER['REQUEST_METHOD'] ?? null,
            'uri'       => $_SERVER['REQUEST_URI'] ?? null,
        ];

        if ($e instanceof UnprocessableEntity && $e->getErrors() !== []) {
            $context['errors'] = $e->getErrors();
        }

        if ($status >= 500) {
            $this->logger->error($e->getMessage(), $context);
            return;
        }

        if ($status >= 400) {
            $this->logger->warning($e->getMessage(), $context);
        }
    }

    private function titleFromStatus(int $status): string
    {
        return match ($status) {
            400     => 'Bad Request',
            401     => 'Unauthorized',
            403     => 'Forbidden',
            404     => 'Not Found',
            422     => 'Unprocessable Entity',
            500     => 'Internal Server Error',
            default => 'Error',
        };
    }
}
