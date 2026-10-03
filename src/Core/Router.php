<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Roteador simples.
 * Fluxo: rotas são registradas em arrays por método HTTP ->
 *        dispatch() normaliza a URL -> encontra "Controller@metodo" ->
 *        instancia o controller e chama o método.
 */
class Router
{
    /** @var array<string, array<string, string>> Ex.: $rotas['GET']['/login'] = 'AuthController@login' */
    private array $rotas = [
        'GET'  => [],
        'POST' => [],
    ];

    /** Namespace onde ficam os controllers. */
    private string $namespaceControllers = 'App\\Controller\\';

    /**
     * @param string $basePath Prefixo da URL quando o projeto roda em subpasta
     *                         (ex.: "/bazar-universitario/public" no XAMPP). Vazio no php -S.
     */
    public function __construct(private string $basePath = '')
    {
    }

    public function get(string $uri, string $action): void
    {
        $this->rotas['GET'][$this->normalizar($uri)] = $action;
    }

    public function post(string $uri, string $action): void
    {
        $this->rotas['POST'][$this->normalizar($uri)] = $action;
    }

    /**
     * Encontra a rota correspondente e executa o controller.
     */
    public function dispatch(string $uri, string $method): void
    {
        $method = strtoupper($method);

        // 1) Remove a query string: "/login?x=1" -> "/login"
        $caminho = parse_url($uri, PHP_URL_PATH) ?: '/';

        // 2) Remove o prefixo da subpasta (caso XAMPP)
        if ($this->basePath !== '' && str_starts_with($caminho, $this->basePath)) {
            $caminho = substr($caminho, strlen($this->basePath));
        }

        // 3) Remove "/index.php" caso o usuário acesse a URL com ele
        if (str_starts_with($caminho, '/index.php')) {
            $caminho = substr($caminho, strlen('/index.php'));
        }

        $caminho = $this->normalizar($caminho);

        // 4) Procura a rota registrada
        $action = $this->rotas[$method][$caminho] ?? null;

        if ($action === null) {
            // Rota existe em outro método? Então é 405, senão 404.
            foreach ($this->rotas as $outroMetodo => $rotasDoMetodo) {
                if ($outroMetodo !== $method && isset($rotasDoMetodo[$caminho])) {
                    $this->erro(405, 'Método não permitido para esta página.');
                    return;
                }
            }
            $this->erro(404, 'Página não encontrada.');
            return;
        }

        // 5) Separa "AuthController@login" em classe e método
        [$nomeController, $nomeMetodo] = explode('@', $action);
        $classe = $this->namespaceControllers . $nomeController;

        if (!class_exists($classe) || !method_exists($classe, $nomeMetodo)) {
            $this->erro(500, "Ação '{$action}' não encontrada.");
            return;
        }

        // 6) Instancia e executa
        $controller = new $classe();
        $controller->$nomeMetodo();
    }

    /** Garante o formato "/rota" sem barra no final (exceto a raiz "/"). */
    private function normalizar(string $uri): string
    {
        $uri = '/' . trim($uri, '/');
        return $uri === '' ? '/' : $uri;
    }

    /** Página de erro mínima (404, 405, 500). */
    private function erro(int $codigo, string $mensagem): void
    {
        http_response_code($codigo);
        $titulo = "Erro {$codigo}";
        $voltar = htmlspecialchars($this->basePath . '/', ENT_QUOTES, 'UTF-8');
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>{$titulo} | Bazar Universitário</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="d-flex align-items-center justify-content-center vh-100" style="background:#f3f5f9">
            <div class="text-center">
                <h1 class="display-3 fw-bold" style="color:#0b3168">{$codigo}</h1>
                <p class="lead">{$mensagem}</p>
                <a href="{$voltar}" class="btn" style="background:#f4b41a;color:#0b3168;font-weight:600">Voltar ao início</a>
            </div>
        </body>
        </html>
        HTML;
    }
}
