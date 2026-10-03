<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Classe base de todos os controllers.
 * Concentra o que é comum: renderizar views, redirecionar,
 * mensagens flash, proteção CSRF e verificação de login.
 */
abstract class Controller
{
    /**
     * Renderiza uma view dentro do layout padrão (header + view + footer).
     *
     * @param string $view  Caminho relativo a src/View, sem ".php" (ex.: "auth/login")
     * @param array<string, mixed> $data Variáveis disponíveis na view (ex.: ['titulo' => 'Login'])
     */
    protected function render(string $view, array $data = []): void
    {
        $arquivoView = dirname(__DIR__) . '/View/' . $view . '.php';

        if (!is_file($arquivoView)) {
            http_response_code(500);
            echo 'View não encontrada: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        // extract() transforma ['titulo' => 'X'] em $titulo = 'X' dentro da view.
        // EXTR_SKIP impede que $data sobrescreva variáveis já existentes (ex.: $arquivoView).
        extract($data, EXTR_SKIP);

        // Mensagem flash é lida (e apagada) aqui para o header exibir
        $flash = $this->pegarFlash();

        require dirname(__DIR__) . '/View/layout/header.php';
        require $arquivoView;
        require dirname(__DIR__) . '/View/layout/footer.php';
    }

    /**
     * Redireciona para outra rota da aplicação e encerra o script.
     * Aceita rotas internas ("/login"); o BASE_URL é adicionado automaticamente.
     */
    protected function redirect(string $url): never
    {
        if (str_starts_with($url, '/')) {
            $url = BASE_URL . $url;
        }
        header('Location: ' . $url);
        exit;
    }

    // ---------------------------------------------------------
    // Mensagens flash: exibidas uma única vez após um redirect
    // ---------------------------------------------------------

    /** @param string $tipo Classe de alerta do Bootstrap: success, danger, warning, info */
    protected function flash(string $tipo, string $mensagem): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
    }

    /** @return array{tipo:string,mensagem:string}|null */
    private function pegarFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    // ---------------------------------------------------------
    // "Old input": valores digitados, devolvidos ao formulário após um erro
    // ---------------------------------------------------------

    /** @param array<string, mixed> $valores */
    protected function guardarOld(array $valores): void
    {
        $_SESSION['old'] = $valores;
    }

    /** @return array<string, mixed> Lidos uma única vez (depois são apagados). */
    protected function pegarOld(): array
    {
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        return $old;
    }

    /**
     * Atalho: mostra o erro e volta ao formulário com os valores digitados.
     *
     * @param array<string, mixed> $old
     */
    protected function voltarComErro(string $rota, string $mensagem, array $old = []): never
    {
        $this->flash('danger', $mensagem);
        $this->guardarOld($old);
        $this->redirect($rota);
    }

    // ---------------------------------------------------------
    // Proteção CSRF: garante que o POST veio de um formulário do próprio site
    // ---------------------------------------------------------

    /** Lança 419 e encerra se o token enviado não bater com o da sessão. */
    protected function validarCsrf(): void
    {
        // Se o envio passar do post_max_size do php.ini, o PHP descarta TUDO
        // ($_POST e $_FILES chegam vazios). Sem esta checagem o usuário veria
        // "sessão expirada", o que confunde. Mostramos o motivo real.
        if ($_POST === [] && $_FILES === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $this->flash('danger', 'O envio passou do limite do servidor. A foto deve ter no máximo 2 MB.');
            $this->redirect($this->paginaAnterior());
        }

        $tokenEnviado = $_POST['csrf_token'] ?? '';
        $tokenSessao  = $_SESSION['csrf_token'] ?? '';

        // hash_equals compara em tempo constante (evita ataques de timing)
        if (!is_string($tokenEnviado) || $tokenSessao === '' || !hash_equals($tokenSessao, $tokenEnviado)) {
            http_response_code(419);
            exit('Sessão expirada ou requisição inválida. Volte e recarregue a página.');
        }
    }

    /**
     * Volta para a página de onde o usuário veio, mas SÓ se for do próprio site
     * (evita "open redirect" para domínios externos via cabeçalho Referer).
     */
    protected function paginaAnterior(): string
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $host    = parse_url($referer, PHP_URL_HOST);

        if ($referer !== '' && $host !== null && $host === parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) {
            return $referer;
        }
        return '/';
    }

    // ---------------------------------------------------------
    // Helpers de autenticação
    // ---------------------------------------------------------

    protected function usuarioLogado(): bool
    {
        return isset($_SESSION['usuario_id']);
    }

    /** ID do usuário da sessão, ou null para visitantes. */
    protected function idUsuarioLogado(): ?int
    {
        return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
    }

    /** Usado em páginas restritas (Etapa 2): manda para o login se não estiver autenticado. */
    protected function exigirLogin(): void
    {
        if (!$this->usuarioLogado()) {
            $this->flash('warning', 'Faça login para continuar.');
            $this->redirect('/login');
        }
    }

    /** Usado em login/cadastro: quem já está logado não precisa ver essas telas. */
    protected function exigirVisitante(): void
    {
        if ($this->usuarioLogado()) {
            $this->redirect('/');
        }
    }
}
