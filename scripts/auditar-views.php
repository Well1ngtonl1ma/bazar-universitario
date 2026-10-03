<?php

declare(strict_types=1);

/**
 * Auditoria de XSS nas views.
 *
 * Lê todos os arquivos de src/View com o tokenizer do PHP e lista cada
 * expressão de "echo curto" que NÃO começa com uma função de escape
 * conhecida: e(), url(), csrf_field(), (int) ou nl2br(e()).
 *
 * Uso (na raiz do projeto):  php scripts/auditar-views.php
 *
 * O que aparecer na lista deve ser conferido à mão: só pode ser texto fixo
 * (ex.: um ternário que imprime ' ativo') ou variável que já veio de url().
 */

$pastaViews = dirname(__DIR__) . '/src/View';
$seguros    = '/^(e\(|url\(|csrf_field\(\)|\(int\)|nl2br\(e\()/';
$total      = 0;
$revisar    = 0;

$arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pastaViews, FilesystemIterator::SKIP_DOTS));

foreach ($arquivos as $arquivo) {
    if (!str_ends_with((string) $arquivo, '.php')) {
        continue;
    }

    $tokens    = token_get_all((string) file_get_contents((string) $arquivo));
    $expressao = null;
    $linha     = 0;

    foreach ($tokens as $token) {
        // Início de um echo curto
        if (is_array($token) && $token[0] === T_OPEN_TAG_WITH_ECHO) {
            $expressao = '';
            $linha     = $token[2];
            continue;
        }
        // Fim do echo: analisa a expressão acumulada
        if ($expressao !== null && is_array($token) && $token[0] === T_CLOSE_TAG) {
            $total++;
            $expressao = trim((string) preg_replace('/\s+/', ' ', $expressao));
            if (!preg_match($seguros, $expressao)) {
                $revisar++;
                $relativo = substr((string) $arquivo, strlen($pastaViews) + 1);
                echo str_pad("{$relativo}:{$linha}", 28) . $expressao . PHP_EOL;
            }
            $expressao = null;
            continue;
        }
        if ($expressao !== null) {
            $expressao .= is_array($token) ? $token[1] : $token;
        }
    }
}

echo PHP_EOL . "{$total} saídas analisadas, {$revisar} para conferir manualmente." . PHP_EOL;
