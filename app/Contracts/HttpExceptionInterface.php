<?php

namespace App\Contracts;

/**
 * Marca uma exceção como sendo originada de um erro HTTP conhecido.
 *
 * Qualquer exceção que implemente esta interface é tratada pelo ExceptionHandler
 * como um erro previsível — ou seja, tem um status HTTP e uma mensagem adequados
 * para serem devolvidos ao cliente na resposta.
 *
 * Exemplos de uso: NotFound (404), BadRequest (400), Unauthorized (401).
 */
interface HttpExceptionInterface
{
    /**
     * Retorna o código de status HTTP correspondente ao erro.
     */
    public function getStatusCode(): int;

    /**
     * Retorna a mensagem descritiva do erro.
     *
     * Esta mensagem é exposta diretamente na resposta JSON, portanto
     * deve conter apenas informações seguras para o cliente.
     */
    public function getMessage(): string;
}
