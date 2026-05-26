<?php

namespace App\Contracts;

/**
 * Contrato que representa a requisição HTTP recebida pela aplicação.
 *
 * Abstrai o acesso aos dados da requisição (query string, corpo, headers)
 * de forma que os controllers não dependam diretamente das superglobais
 * do PHP ($_GET, $_POST, $_SERVER), tornando o código mais testável e desacoplado.
 */
interface RequestInterface
{
    /**
     * Retorna o método HTTP da requisição em letras maiúsculas.
     *
     * Exemplos: 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'.
     */
    public function method(): string;

    /**
     * Retorna o valor do header Content-Type da requisição.
     *
     * Retorna uma string vazia caso o header não tenha sido enviado.
     */
    public function contentType(): string;

    /**
     * Verifica se o método HTTP da requisição corresponde ao informado.
     *
     * A comparação é insensível a maiúsculas e minúsculas.
     *
     * @param string $method Método a comparar (ex: 'get', 'POST').
     */
    public function isMethod(string $method): bool;

    /**
     * Retorna um parâmetro da query string ($_GET).
     *
     * @param string $key     Nome do parâmetro.
     * @param mixed  $default Valor retornado caso o parâmetro não exista.
     */
    public function query(string $key, mixed $default = null): mixed;

    /**
     * Retorna um campo do corpo da requisição ($_POST).
     *
     * @param string $key     Nome do campo.
     * @param mixed  $default Valor retornado caso o campo não exista.
     */
    public function post(string $key, mixed $default = null): mixed;

    /**
     * Busca um valor primeiro na query string e, se não encontrar, no corpo da requisição.
     *
     * Útil quando o mesmo parâmetro pode vir tanto via GET quanto via POST.
     *
     * @param string $key     Nome do parâmetro.
     * @param mixed  $default Valor retornado caso não exista em nenhuma das fontes.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Retorna todos os parâmetros da requisição, unindo query string e corpo.
     *
     * Em caso de chaves duplicadas, os valores do corpo sobrescrevem os da query string.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Retorna os dados do corpo da requisição com sanitização básica.
     *
     * Aplica FILTER_SANITIZE_SPECIAL_CHARS em cada valor para prevenir
     * injeção de caracteres especiais vindos de formulários.
     *
     * @return array<string, mixed>
     */
    public function getBody(): array;

    /**
     * Decodifica e retorna o corpo da requisição como array quando o Content-Type for JSON.
     *
     * Aceita qualquer variante de 'application/json' (incluindo com charset).
     * Retorna um array vazio se o Content-Type não for JSON ou se o corpo estiver vazio.
     */
    public function getJSON(): mixed;
}
