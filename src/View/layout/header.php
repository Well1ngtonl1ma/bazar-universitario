<?php
/**
 * Layout: cabeçalho + navbar.
 * Variáveis disponíveis: $titulo (opcional), $flash (mensagem única, opcional).
 *
 * Toda saída dinâmica passa por e() (texto) ou url() (links, já escapados).
 */
$logadoNav = isset($_SESSION['usuario_id']);
$nomeNav   = (string) ($_SESSION['usuario_nome'] ?? '');

// Caminho atual sem o BASE_URL, para destacar o link ativo no menu
$rotaAtual = substr((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(BASE_URL));
$rotaAtual = '/' . trim($rotaAtual, '/');
$ativo = fn (string $rota): string => $rotaAtual === $rota ? ' active' : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Bazar Universitário') ?> | Bazar Universitário</title>

    <!-- Bootstrap 5 e ícones via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Tema UNAERP (azul marinho, amarelo ouro, branco) -->
    <link href="<?= url('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-bazar shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= url('/') ?>">
            <i class="bi bi-bag-heart-fill brand-destaque"></i>
            Bazar <span class="brand-destaque">Universitário</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
                aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link<?= $ativo('/') ?>" href="<?= url('/') ?>">Vitrine</a></li>
                <?php if ($logadoNav): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $ativo('/dashboard') ?>" href="<?= url('/dashboard') ?>">
                            <i class="bi bi-speedometer2"></i> Meu painel
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav align-items-lg-center gap-lg-2">
                <?php if ($logadoNav): ?>
                    <!-- Usuário autenticado -->
                    <li class="nav-item">
                        <a class="btn btn-sm btn-ouro" href="<?= url('/itens/criar') ?>">
                            <i class="bi bi-plus-circle"></i> Anunciar Item
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="bi bi-person-circle"></i>
                            Olá, <strong><?= e($nomeNav) ?></strong>
                        </span>
                    </li>
                    <li class="nav-item">
                        <!-- Logout é um POST com token CSRF -->
                        <form action="<?= url('/logout') ?>" method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-light">
                                <i class="bi bi-box-arrow-right"></i> Sair
                            </button>
                        </form>
                    </li>
                <?php else: ?>
                    <!-- Visitante -->
                    <li class="nav-item"><a class="nav-link<?= $ativo('/login') ?>" href="<?= url('/login') ?>">Entrar</a></li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-ouro" href="<?= url('/cadastro') ?>">Criar conta</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="py-4">
    <div class="container">
        <?php if (!empty($flash)): ?>
            <!-- Mensagem flash (exibida uma única vez) -->
            <div class="alert alert-<?= e($flash['tipo']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['mensagem']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>
