<?php

namespace App\Contracts;

/**
 * Marca uma exceção como sendo um erro interno da aplicação.
 *
 * Funciona como uma interface sinalizadora: ao estender HttpExceptionInterface,
 * garante que o ExceptionHandler consiga identificar erros internos antes de
 * erros HTTP comuns — o que é necessário porque RuntimeExceptionInterface é
 * verificado primeiro no fluxo de tratamento.
 *
 * A diferença prática em relação a HttpExceptionInterface é que a mensagem
 * real do erro nunca é exposta ao cliente em ambiente de produção, evitando
 * vazamento de detalhes sensíveis como stack traces ou mensagens de banco.
 *
 * Exemplo de uso: InternalServerError (500).
 */
interface RuntimeExceptionInterface extends HttpExceptionInterface
{
}
