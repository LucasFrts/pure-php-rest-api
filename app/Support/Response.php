<?php

namespace App\Support;

use App\Contracts\EntityInterface;
use App\Contracts\ResponseInterface;

/**
 * Constrói e envia respostas HTTP em formato JSON.
 *
 */
class Response implements ResponseInterface
{
    /** Código de status HTTP da resposta. Padrão: 200. */
    private int $status = 200;

    /** @var array<string, string> Headers HTTP da resposta. Content-Type JSON por padrão. */
    private array $headers = ['Content-Type' => 'application/json'];

    /** @var array<string, mixed> Dados a serializar no corpo da resposta. */
    private array $data = [];

    /**
     * Define o código de status HTTP da resposta.
     *
     * @param int $status Código HTTP (ex: 200, 201, 404).
     */
    public function withStatus(int $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Adiciona ou substitui um header HTTP na resposta.
     *
     * @param string $name  Nome do header (ex: 'Location', 'X-Custom-Id').
     * @param string $value Valor do header.
     */
    public function withHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Define os dados que serão serializados como JSON no corpo da resposta.
     *
     * @param array<string, mixed> $data Dados a serializar.
     */
    public function json(array $data): static
    {
        $this->data = array_map([$this, 'normalize'], $data);
        return $this;
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof EntityInterface) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map([$this, 'normalize'], $value);
        }

        return $value;
    }

    /**
     * Envia a resposta ao cliente: emite o status HTTP, os headers e o corpo JSON.
     *
     * Chamado automaticamente pelo Dispatcher após a execução do controller.
     * Nenhum output deve ter ocorrido antes desta chamada.
     */
    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo json_encode($this->data);
    }

    /**
     * Aplica status, dados e headers na resposta e a retorna para encadeamento.
     *
     * @param int                   $status  Código HTTP a aplicar.
     * @param array<string, mixed>  $data    Dados opcionais do corpo.
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    private function applyShorthand(int $status, array $data, array $headers): static
    {
        $this->withStatus($status);
        if ($data !== []) {
            $this->json($data);
        }
        foreach ($headers as $name => $value) {
            $this->withHeader($name, $value);
        }
        return $this;
    }

    /**
     * Configura a resposta como 200 OK.
     *
     * @param array<string, mixed>  $data    Dados opcionais do corpo.
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function success(array $data = [], array $headers = []): static
    {
        return $this->applyShorthand(200, $data, $headers);
    }

    /**
     * Configura a resposta como 201 Created.
     *
     * Idealmente acompanhado do header Location apontando para o recurso criado.
     *
     * @param array<string, mixed>  $data    Dados opcionais do recurso criado.
     * @param array<string, string> $headers Headers opcionais (ex: ['Location' => '/users/1']).
     */
    public function created(array $data = [], array $headers = []): static
    {
        return $this->applyShorthand(201, $data, $headers);
    }

    /**
     * Configura a resposta como 204 No Content.
     *
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function noContent(array $headers = []): static
    {
        return $this->applyShorthand(204, [], $headers);
    }

    /**
     * Configura a resposta como 400 Bad Request.
     *
     * Indica que a requisição está malformada ou com parâmetros inválidos.
     *
     * @param array<string, mixed>  $data    Detalhes opcionais do erro.
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function badRequest(array $data = [], array $headers = []): static
    {
        return $this->applyShorthand(400, $data, $headers);
    }

    /**
     * Configura a resposta como 401 Unauthorized.
     *
     * Indica que a requisição requer autenticação válida.
     *
     * @param array<string, mixed>  $data    Detalhes opcionais do erro.
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function unauthorized(array $data = [], array $headers = []): static
    {
        return $this->applyShorthand(401, $data, $headers);
    }

    /**
     * Configura a resposta como 403 Forbidden.
     *
     * @param array<string, mixed>  $data    Detalhes opcionais do erro.
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function forbidden(array $data = [], array $headers = []): static
    {
        return $this->applyShorthand(403, $data, $headers);
    }

    /**
     * Configura a resposta como 404 Not Found.
     *
     * Indica que o recurso solicitado não foi encontrado.
     *
     * @param array<string, mixed>  $data    Detalhes opcionais do erro.
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function notFound(array $data = [], array $headers = []): static
    {
        return $this->applyShorthand(404, $data, $headers);
    }

    /**
     * Configura a resposta como 422 Unprocessable Entity.
     *
     * Usado para erros de validação: a requisição está bem formada mas os
     * dados não passam nas regras de negócio.
     *
     * @param array<string, mixed>  $data    Erros de validação opcionais.
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function unprocessable(array $data = [], array $headers = []): static
    {
        return $this->applyShorthand(422, $data, $headers);
    }

    /**
     * Configura a resposta como 500 Internal Server Error.
     *
     * @param array<string, mixed>  $data    Detalhes opcionais (apenas em desenvolvimento).
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function serverError(array $data = [], array $headers = []): static
    {
        return $this->applyShorthand(500, $data, $headers);
    }

    /**
     * Retorna o status atual da resposta. Uso exclusivo em testes.
     */
    public function getStatusForTest(): int
    {
        return $this->status;
    }

    public function getDataForTest(): array
    {
        return $this->data;
    }

    public function getHeadersForTest(): array
    {
        return $this->headers;
    }
}
