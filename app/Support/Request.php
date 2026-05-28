<?php

namespace App\Support;

use App\Contracts\RequestInterface;
use App\Exceptions\Http\BadRequest;
use Stringable;

/**
 * Representa a requisição HTTP atual recebida pela aplicação.
 *
 */
class Request implements RequestInterface
{
    private array $queryParams;

    private array $postParams;

    private string $requestMethod;

    private string $contentType;

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

    public function contentType(): string
    {
        return $this->contentType;
    }

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
            return $this->postParams;
        }
        $content = trim(file_get_contents('php://input'));
        $data = json_decode($content, true) ?? [];

        if (!is_array($data) && !($data instanceof Stringable)){
            throw new BadRequest("Verifique o conteúdo da requisição e tente novamente.");
        }

        return $data;
    }
}
