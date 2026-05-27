<?php

/**
 * Configura tratamento de erros para a API: nada é exibido no corpo da resposta.
 * Warnings e notices viram ErrorException, capturadas pelo try/catch em index.php.
 */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

set_error_handler(
    static function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }
);
