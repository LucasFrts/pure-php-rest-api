<?php

namespace App\Support;

use App\Contracts\ConfigInterface;
use App\Contracts\HttpExceptionInterface;
use App\Contracts\RuntimeExceptionInterface;
use Throwable;

/**
 * Centraliza o tratamento de todas as exceções não capturadas da aplicação.
 *
 * Classifica as exceções em três categorias e decide o que expor ao cliente:
 *
 * 1. RuntimeExceptionInterface — erros internos do servidor (ex: falha no banco,
 *    serviço indisponível). Em produção, a mensagem real é ocultada para não
 *    vazar detalhes sensíveis; em ambiente local, é exposta para facilitar o debug.
 *
 * 2. HttpExceptionInterface — erros HTTP conhecidos e seguros de expor (ex: 404,
 *    400, 422). A mensagem é sempre retornada ao cliente pois foi definida
 *    intencionalmente pelo desenvolvedor.
 *
 * 3. Throwable genérico — exceções inesperadas sem categoria. Tratadas como
 *    erro 500, com a mesma política de ocultamento que RuntimeExceptionInterface.
 *
 * Todas as respostas seguem o formato RFC 7807 (Problem Details for HTTP APIs),
 * com Content-Type: application/problem+json.
 */
class ExceptionHandler
{
    /**
     * @param ConfigInterface $config Configurações da aplicação, usadas para verificar APP_ENV.
     */
    public function __construct(private ConfigInterface $config)
    {
    }

    /**
     * Processa a exceção e emite a resposta de erro adequada ao cliente.
     *
     * Verifica RuntimeExceptionInterface antes de HttpExceptionInterface porque
     * RuntimeExceptionInterface também estende HttpExceptionInterface — sem essa
     * ordem, erros internos seriam tratados como erros HTTP simples e teriam
     * suas mensagens expostas em produção.
     *
     * @param Throwable $e Qualquer exceção ou erro lançado pela aplicação.
     */
    public function handle(Throwable $e): void
    {
        if ($e instanceof RuntimeExceptionInterface) {
            $status = $e->getStatusCode();
            $detail = $this->isDebug() ? $e->getMessage() : 'Internal Server Error';
        } elseif ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $detail = $e->getMessage();
        } else {
            $status = 500;
            $detail = $this->isDebug() ? $e->getMessage() : 'Internal Server Error';
        }

        http_response_code($status);
        header('Content-Type: application/problem+json');
        echo json_encode([
            'status' => $status,
            'title'  => $this->titleFromStatus($status),
            'detail' => $detail,
        ]);
    }

    /**
     * Verifica se a aplicação está rodando em modo de desenvolvimento.
     *
     * Apenas o valor 'local' é considerado ambiente de debug. Qualquer outro
     * valor (production, staging, testing) mantém os detalhes internos ocultos.
     */
    private function isDebug(): bool
    {
        return $this->config->get('APP_ENV', 'production') === 'local';
    }

    /**
     * Retorna o título legível correspondente ao código de status HTTP.
     *
     * Segue a nomenclatura padrão do protocolo HTTP para os status mais comuns.
     * Status não mapeados retornam 'Error' como fallback genérico.
     *
     * @param int $status Código de status HTTP.
     */
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
