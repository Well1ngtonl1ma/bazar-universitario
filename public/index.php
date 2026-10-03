<?php

declare(strict_types=1);

/**
 * FRONT CONTROLLER
 * Toda requisição passa por aqui. Fluxo:
 *   0) arquivos estáticos (só no servidor embutido do PHP)
 *   1) ambiente (local x produção) e cabeçalhos de segurança
 *   2) sessão segura + token CSRF
 *   3) autoload (App\ -> src/)
 *   4) BASE_URL e helpers das views
 *   5) rotas -> dispatch para o controller certo
 */

use App\Core\Router;

// -------------------------------------------------------------
// 0) Servidor embutido (php -S): serve css/js/imagens existentes em public/.
//    No Apache, quem faz isso é o .htaccess.
// -------------------------------------------------------------
if (PHP_SAPI === 'cli-server') {
    $caminho = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $arquivo = __DIR__ . $caminho;

    // Mesma regra do uploads/.htaccess: dessa pasta, só imagens
    if (str_contains($caminho, '/uploads/') && !preg_match('/\.(jpg|png|webp)$/', $caminho)) {
        http_response_code(404);
        exit;
    }
    if ($arquivo !== __FILE__ && is_file($arquivo)) {
        return false;
    }
}

// -------------------------------------------------------------
// 1) Ambiente: APP_ENV=production vem do Apache no servidor (SetEnv).
//    Em produção, erros vão só para o log, nunca para a tela.
// -------------------------------------------------------------
define('APP_ENV', (string) ($_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: 'local'));

// Fuso horário fixo: XAMPP vem com Europe/Berlin e a VM do Google com UTC.
// APP_TZ permite trocar pelo Apache, se um dia precisar.
date_default_timezone_set((string) ($_SERVER['APP_TZ'] ?? getenv('APP_TZ') ?: 'America/Sao_Paulo'));

error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'production' ? '0' : '1');
ini_set('log_errors', '1');

// Cabeçalhos de segurança enviados em todas as páginas
header('X-Content-Type-Options: nosniff');          // navegador não "adivinha" o tipo do arquivo
header('X-Frame-Options: SAMEORIGIN');              // impede o site de ser embutido em iframe alheio (clickjacking)
header('Referrer-Policy: strict-origin-when-cross-origin');
// CSP: só carrega scripts do próprio site e do CDN do Bootstrap. Um <script> injetado não roda.
header("Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' https://cdn.jsdelivr.net; "
    . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
    . "font-src 'self' https://cdn.jsdelivr.net; "
    . "img-src 'self' data: blob:; "
    . "form-action 'self'; frame-ancestors 'self'; base-uri 'self'; object-src 'none'");

// -------------------------------------------------------------
// 2) Sessão com cookies mais seguros
// -------------------------------------------------------------
ini_set('session.use_strict_mode', '1');   // recusa IDs de sessão inventados pelo cliente
session_set_cookie_params([
    'lifetime' => 0,          // expira ao fechar o navegador
    'path'     => '/',
    'httponly' => true,       // JavaScript não acessa o cookie (mitiga XSS)
    'samesite' => 'Lax',      // não envia o cookie em POSTs vindos de outros sites
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // só HTTPS, quando houver
]);
session_start();

// Token CSRF único por sessão, usado em todos os formulários POST
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// -------------------------------------------------------------
// 3) Autoload PSR-4 simplificado (sem Composer)
//    App\Core\Router  ->  src/Core/Router.php
// -------------------------------------------------------------
define('ROOT_PATH', dirname(__DIR__));

spl_autoload_register(function (string $classe): void {
    $prefixo = 'App\\';
    if (!str_starts_with($classe, $prefixo)) {
        return; // não é uma classe do nosso projeto
    }

    $relativo = substr($classe, strlen($prefixo));                 // "Core\Router"
    $arquivo  = ROOT_PATH . '/src/' . str_replace('\\', '/', $relativo) . '.php';

    if (is_file($arquivo)) {
        require $arquivo;
    }
});

// -------------------------------------------------------------
// 4) BASE_URL: prefixo das URLs, calculado sozinho.
//    XAMPP  (htdocs/bazar-universitario/public) -> "/bazar-universitario/public"
//    Ubuntu (DocumentRoot = .../public) e php -S -> ""
// -------------------------------------------------------------
$base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
define('BASE_URL', rtrim($base, '/'));

/**
 * Escapa texto para HTML (proteção contra XSS).
 * REGRA DO PROJETO: todo dado dinâmico impresso nas views passa por e().
 */
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Monta uma URL interna JÁ ESCAPADA para uso em atributos HTML.
 * url('/login') -> "/bazar-universitario/public/login"
 */
function url(string $caminho = '/'): string
{
    return e(BASE_URL . '/' . ltrim($caminho, '/'));
}

/** Campo oculto com o token CSRF para colocar dentro de cada <form method="post">. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

// -------------------------------------------------------------
// 5) Rotas: URL -> "Controller@metodo"
// -------------------------------------------------------------
$router = new Router(BASE_URL);

// Público
$router->get('/', 'HomeController@index');                            // vitrine (?categoria=X)
$router->get('/itens/detalhes', 'ItemController@detalhes');           // ?id=X

// Autenticação
$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@autenticar');
$router->get('/cadastro', 'AuthController@cadastro');
$router->post('/cadastro', 'AuthController@registrar');
$router->post('/logout', 'AuthController@logout');                    // POST + CSRF

// Itens (logado). O ID vai na query string nos GET e em campo oculto nos POST.
$router->get('/itens/criar', 'ItemController@criar');                 // formulário
$router->post('/itens/criar', 'ItemController@salvar');               // INSERT + upload
$router->get('/itens/editar', 'ItemController@editar');               // formulário (dono)
$router->post('/itens/editar', 'ItemController@atualizar');           // UPDATE + upload (dono)
$router->post('/itens/deletar', 'ItemController@deletar');            // DELETE (dono)
$router->post('/itens/concluir', 'ItemController@concluir');          // status -> concluido (dono)
$router->post('/itens/reabrir', 'ItemController@reabrir');            // concluido -> disponivel (dono)
$router->post('/itens/interesse', 'ItemController@manifestarInteresse');

// Painel do usuário
$router->get('/dashboard', 'ItemController@dashboard');
$router->get('/meus-itens', 'ItemController@meusItens');              // compatibilidade: redireciona p/ /dashboard

// -------------------------------------------------------------
// 6) Rede de segurança: qualquer exceção não tratada (ex.: banco caiu)
//    vira uma página amigável, e o detalhe técnico vai só para o log.
// -------------------------------------------------------------
set_exception_handler(function (Throwable $e): void {
    error_log('[Bazar] ' . $e::class . ': ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);

    // Em ambiente local, mostra o erro para facilitar a depuração
    $detalhe = APP_ENV !== 'production'
        ? '<pre class="text-start small bg-white border rounded p-3 mt-3">' . e($e->getMessage()) . '</pre>'
        : '';

    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Erro | Bazar Universitário</title>'
       . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
       . '<body class="d-flex align-items-center justify-content-center vh-100" style="background:#f3f5f9">'
       . '<div class="text-center container" style="max-width:640px"><h1 class="display-4 fw-bold" style="color:#0b3168">Ops!</h1>'
       . '<p class="lead">Algo deu errado. Verifique se o MySQL está ligado e tente novamente.</p>'
       . $detalhe
       . '<a href="' . url('/') . '" class="btn mt-2" style="background:#f4b41a;color:#0b3168;font-weight:600">Voltar à vitrine</a>'
       . '</div></body></html>';
});

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
