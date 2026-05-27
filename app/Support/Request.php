<?php

namespace App\Support;

use App\Contracts\RequestInterface;

/**
 * Representa a requisição HTTP atual recebida pela aplicação.
 *
 * Captura os dados das superglobais ($_SERVER, $_GET, $_POST) no momento
 * da construção, encapsulando o acesso em métodos tipados e testáveis.
 * Ao isolar o uso direto das superglobais nesta classe, os controllers
 * ficam desacoplados do contexto global do PHP e podem ser testados
 * facilmente manipulando as superglobais antes de instanciar Request.
 */
class Request implements RequestInterface
{
    /** @var array<string, mixed> Parâmetros da query string ($_GET). */
    private array $queryParams;

    /** @var array<string, mixed> Campos do corpo da requisição ($_POST). */
    private array $postParams;

    /** Método HTTP da requisição em letras maiúsculas (ex: 'GET', 'POST'). */
    private string $requestMethod;

    /** Valor do header Content-Type, ou string vazia se ausente. */
    private string $contentType;

    /**
     * Lê e armazena os dados da requisição a partir das superglobais do PHP.
     */
    public function __construct()
    {
        $this->requestMethod = strtoupper(trim($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $this->contentType   = !empty($_SERVER['CONTENT_TYPE']) ? trim($_SERVER['CONTENT_TYPE']) : '';
        $this->queryParams   = $_GET ?? [];
        $this->postParams    = $_POST ?? [];
    }

    /**
     * Retorna o método HTTP da requisição em letras maiúsculas.
     *
     * Exemplos: 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'.
     */
    public function method(): string
    {
        return $this->requestMethod;
    }

    /**
     * Retorna o valor do header Content-Type da requisição.
     *
     * Retorna uma string vazia caso o header não tenha sido enviado.
     */
    public function contentType(): string
    {
        return $this->contentType;
    }

    /**
     * Verifica se o método HTTP da requisição corresponde ao informado.
     *
     * A comparação é insensível a maiúsculas e minúsculas.
     *
     * @param string $method Método a comparar (ex: 'get', 'POST').
     */
    public function isMethod(string $method): bool
    {
        return $this->requestMethod === strtoupper($method);
    }

    /**
     * Retorna um parâmetro da query string ($_GET).
     *
     * @param string $key     Nome do parâmetro.
     * @param mixed  $default Valor retornado caso o parâmetro não exista.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Retorna um campo do corpo da requisição ($_POST).
     *
     * @param string $key     Nome do campo.
     * @param mixed  $default Valor retornado caso o campo não exista.
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    /**
     * Busca um valor primeiro na query string e, se não encontrar, no corpo da requisição.
     *
     * Útil quando o mesmo parâmetro pode vir tanto via GET quanto via POST.
     *
     * @param string $key     Nome do parâmetro.
     * @param mixed  $default Valor retornado caso não exista em nenhuma das fontes.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $this->postParams[$key] ?? $default;
    }

    /**
     * Retorna todos os parâmetros da requisição, unindo query string e corpo.
     *
     * Em caso de chaves duplicadas, os valores do corpo sobrescrevem os da query string.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->queryParams, $this->postParams);
    }

    /**
     * Retorna os dados do corpo da requisição com sanitização básica.
     *
     * Aplica FILTER_SANITIZE_SPECIAL_CHARS em cada valor para prevenir
     * injeção de caracteres especiais vindos de formulários HTML.
     *
     * @return array<string, mixed>
     */
    public function getBody(): array
    {
        $body = [];
        foreach ($this->postParams as $key => $value) {
            $body[$key] = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
        }
        return $body;
    }

    /**
     * Decodifica e retorna o corpo da requisição como array quando o Content-Type for JSON.
     *
     * Aceita qualquer variante de 'application/json' (incluindo 'application/json; charset=utf-8').
     * Retorna um array vazio se o Content-Type não for JSON ou se o corpo estiver vazio ou inválido.
     */
    public function data(): mixed
    {
        if (!str_starts_with(strtolower($this->contentType), 'application/json')) {
            return [];
        }
        $content = trim(file_get_contents('php://input'));
        return json_decode($content, true) ?? [];
    }
}
