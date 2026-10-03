<?php

/**
 * Credenciais do banco de dados.
 *
 * Cada valor é lido de uma VARIÁVEL DE AMBIENTE e, se ela não existir,
 * usa o padrão do XAMPP. Assim o MESMO código roda nos dois lugares:
 *
 *   XAMPP (local) ....... nenhuma variável definida -> root sem senha
 *   Google Cloud ........ SetEnv DB_* no 000-default.conf do Apache
 *
 * A senha de produção nunca fica no código nem no Git.
 */

/** Lê a variável do Apache ($_SERVER) ou do sistema (getenv); senão, devolve o padrão. */
$env = static function (string $nome, string $padrao): string {
    $valor = $_SERVER[$nome] ?? getenv($nome);
    return ($valor === false || $valor === null || $valor === '') ? $padrao : (string) $valor;
};

return [
    'host'    => $env('DB_HOST', '127.0.0.1'),
    'dbname'  => $env('DB_NAME', 'bazar_universitario'),
    'user'    => $env('DB_USER', 'root'),
    'pass'    => $env('DB_PASS', ''),
    'charset' => 'utf8mb4',
];
