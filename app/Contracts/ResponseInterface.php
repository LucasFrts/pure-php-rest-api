<?php

namespace App\Contracts;

/**
 * Contrato para construção e envio de respostas HTTP em formato JSON.
 *
 * Segue o padrão Fluent Builder: os métodos de configuração retornam
 * a própria instância, permitindo encadear chamadas antes de finalizar
 * com send(). Os atalhos semânticos (success, created, etc.) combinam
 * a definição do status com a opção de já informar os dados e headers,
 * reduzindo a verbosidade nos controllers.
 *
 * Exemplo de uso:
 *   $this->response->created(['id' => 1], ['Location' => '/users/1'])->send();
 */
interface ResponseInterface
{
    /**
     * Define o código de status HTTP da resposta.
     *
     * @param int $status Código HTTP (ex: 200, 201, 404).
     */
    public function withStatus(int $status): static;

    /**
     * Adiciona ou substitui um header HTTP na resposta.
     *
     * @param string $name  Nome do header (ex: 'Location', 'X-Custom-Id').
     * @param string $value Valor do header.
     */
    public function withHeader(string $name, string $value): static;

    /**
     * Define os dados que serão serializados como JSON no corpo da resposta.
     *
     * @param array<string, mixed> $data Dados a serializar.
     */
    public function json(array $data): static;

    /**
     * Envia a resposta ao cliente: emite o status HTTP, os headers e o corpo JSON.
     *
     * Chamado automaticamente pelo Dispatcher após a execução do controller.
     * Nenhum output deve ter ocorrido antes desta chamada.
     */
    public function send(): void;

    /**
     * Configura a resposta como 200 OK.
     *
     * O envio é feito pelo Dispatcher após o controller retornar a instância.
     *
     * @param array<string, mixed>  $data    Dados opcionais do corpo.
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function success(array $data = [], array $headers = []): static;

    /**
     * Configura a resposta como 201 Created.
     *
     * Idealmente acompanhado do header Location apontando para o recurso criado.
     *
     * @param array<string, mixed>  $data    Dados opcionais do recurso criado.
     * @param array<string, string> $headers Headers opcionais (ex: ['Location' => '/users/1']).
     */
    public function created(array $data = [], array $headers = []): static;

    /**
     * Configura a resposta como 204 No Content.
     *
     * Usado quando a operação foi bem-sucedida mas não há corpo na resposta.
     * Não aceita $data pois 204 não deve ter corpo.
     *
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function noContent(array $headers = []): static;

    /**
     * Configura a resposta como 400 Bad Request.
     *
     * Indica que a requisição está malformada ou com parâmetros inválidos.
     *
     * @param array<string, mixed>  $data    Detalhes opcionais do erro.
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function badRequest(array $data = [], array $headers = []): static;

    /**
     * Configura a resposta como 401 Unauthorized.
     *
     * Indica que a requisição requer autenticação válida.
     *
     * @param array<string, mixed>  $data    Detalhes opcionais do erro.
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function unauthorized(array $data = [], array $headers = []): static;

    /**
     * Configura a resposta como 403 Forbidden.
     *
     * Indica que o usuário está autenticado mas não tem permissão para o recurso.
     *
     * @param array<string, mixed>  $data    Detalhes opcionais do erro.
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function forbidden(array $data = [], array $headers = []): static;

    /**
     * Configura a resposta como 404 Not Found.
     *
     * Indica que o recurso solicitado não foi encontrado.
     *
     * @param array<string, mixed>  $data    Detalhes opcionais do erro.
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function notFound(array $data = [], array $headers = []): static;

    /**
     * Configura a resposta como 422 Unprocessable Entity.
     *
     * Usado para erros de validação: a requisição está bem formada mas os
     * dados não passam nas regras de negócio.
     *
     * @param array<string, mixed>  $data    Erros de validação opcionais.
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function unprocessable(array $data = [], array $headers = []): static;

    /**
     * Configura a resposta como 500 Internal Server Error.
     *
     * Usado para erros inesperados do servidor. Em produção, evite incluir
     * detalhes internos no $data para não expor informações sensíveis.
     *
     * @param array<string, mixed>  $data    Detalhes opcionais (apenas em desenvolvimento).
     * @param array<string, string> $headers Headers opcionais adicionais.
     */
    public function serverError(array $data = [], array $headers = []): static;
}
