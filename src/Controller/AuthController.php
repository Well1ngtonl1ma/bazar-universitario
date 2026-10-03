<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Model\Usuario;
use InvalidArgumentException;
use PDOException;

/**
 * Controla login, cadastro e logout.
 * Padrão PRG (Post -> Redirect -> Get): após cada POST fazemos redirect,
 * evitando reenvio do formulário ao apertar F5.
 */
class AuthController extends Controller
{
    /** GET /login */
    public function login(): void
    {
        $this->exigirVisitante();

        $this->render('auth/login', [
            'titulo' => 'Entrar',
            'old'    => $this->pegarOld(),
        ]);
    }

    /** POST /login */
    public function autenticar(): void
    {
        $this->exigirVisitante();
        $this->validarCsrf();

        $email = trim((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');

        // 1) Validação básica dos campos
        if ($email === '' || $senha === '') {
            $this->voltarComErro('/login', 'Informe e-mail e senha.', ['email' => $email]);
        }

        // 2) Busca o usuário e confere a senha
        try {
            $usuario = (new Usuario())->buscarPorEmail($email);
        } catch (PDOException) {
            $this->voltarComErro('/login', 'Erro ao acessar o banco. Tente novamente.', ['email' => $email]);
        }

        // Mensagem genérica de propósito: não revela se o e-mail existe
        if ($usuario === null || !password_verify($senha, $usuario['senha_hash'])) {
            $this->voltarComErro('/login', 'E-mail ou senha incorretos.', ['email' => $email]);
        }

        // 3) Sucesso: grava a sessão e redireciona
        $this->iniciarSessaoUsuario($usuario['id'], $usuario['nome'], $usuario['email']);
        $this->flash('success', 'Bem-vindo(a) de volta, ' . $usuario['nome'] . '!');
        $this->redirect('/');
    }

    /** GET /cadastro */
    public function cadastro(): void
    {
        $this->exigirVisitante();

        $this->render('auth/cadastro', [
            'titulo' => 'Criar conta',
            'old'    => $this->pegarOld(),
        ]);
    }

    /** POST /cadastro */
    public function registrar(): void
    {
        $this->exigirVisitante();
        $this->validarCsrf();

        $nome      = trim((string) ($_POST['nome'] ?? ''));
        $email     = trim((string) ($_POST['email'] ?? ''));
        $senha     = (string) ($_POST['senha'] ?? '');
        $confirmar = (string) ($_POST['confirmar_senha'] ?? '');

        $old = ['nome' => $nome, 'email' => $email]; // nunca devolvemos a senha ao formulário

        // 1) Validações no servidor (o "required" do HTML pode ser burlado)
        $erros = [];
        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 100) {
            $erros[] = 'O nome deve ter entre 3 e 100 caracteres.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $erros[] = 'Informe um e-mail válido.';
        }
        if (strlen($senha) < 8) {
            $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
        }
        if ($senha !== $confirmar) {
            $erros[] = 'As senhas não conferem.';
        }

        if ($erros !== []) {
            $this->voltarComErro('/cadastro', implode(' ', $erros), $old);
        }

        // 2) Tenta salvar (o Model valida e-mail duplicado)
        try {
            $id = (new Usuario())->criar($nome, $email, $senha);
        } catch (InvalidArgumentException $e) {
            $this->voltarComErro('/cadastro', $e->getMessage(), $old);
        } catch (PDOException) {
            $this->voltarComErro('/cadastro', 'Erro ao salvar o cadastro. Tente novamente.', $old);
        }

        // 3) Já entra logado após o cadastro
        $this->iniciarSessaoUsuario($id, $nome, mb_strtolower($email));
        $this->flash('success', 'Conta criada com sucesso! Bem-vindo(a) ao Bazar.');
        $this->redirect('/');
    }

    /** POST /logout */
    public function logout(): void
    {
        $this->validarCsrf();

        // 1) Limpa os dados da sessão
        $_SESSION = [];

        // 2) Apaga o cookie da sessão no navegador
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        // 3) Destrói a sessão no servidor
        session_destroy();

        $this->redirect('/');
    }

    // ---------------------------------------------------------
    // Métodos auxiliares privados
    // ---------------------------------------------------------

    /** Grava os dados do usuário na sessão de forma segura. */
    private function iniciarSessaoUsuario(int $id, string $nome, string $email): void
    {
        // Gera novo ID de sessão: evita "session fixation"
        session_regenerate_id(true);

        $_SESSION['usuario_id']    = $id;
        $_SESSION['usuario_nome']  = $nome;
        $_SESSION['usuario_email'] = $email;
    }

    // voltarComErro() e pegarOld() agora ficam na classe base Controller (Etapa 2),
    // pois o ItemController também usa.
}
