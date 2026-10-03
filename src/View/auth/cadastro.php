<?php
/**
 * Tela de cadastro.
 * Variáveis: $old (nome e e-mail digitados antes de um erro; a senha nunca é devolvida)
 */
?>
<div class="row justify-content-center">
    <div class="col-sm-10 col-md-8 col-lg-6">
        <div class="card card-bazar destaque p-2 p-md-3">
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="bi bi-person-plus fs-1 text-azul"></i>
                    <h1 class="h3 fw-bold text-azul mt-2">Criar conta</h1>
                    <p class="text-muted mb-0">Cadastre-se para anunciar e negociar itens</p>
                </div>

                <!-- POST /cadastro -> AuthController@registrar -->
                <form action="<?= url('/cadastro') ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome completo</label>
                        <input type="text" class="form-control" id="nome" name="nome"
                               value="<?= e($old['nome'] ?? '') ?>" minlength="3" maxlength="100"
                               required autofocus autocomplete="name">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= e($old['email'] ?? '') ?>" maxlength="150"
                               required autocomplete="email">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="senha" name="senha"
                                   minlength="8" required autocomplete="new-password">
                            <div class="form-text">Mínimo de 8 caracteres.</div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="confirmar_senha" class="form-label">Confirmar senha</label>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha"
                                   minlength="8" required autocomplete="new-password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-ouro w-100 py-2">
                        <i class="bi bi-check2-circle"></i> Criar minha conta
                    </button>
                </form>

                <hr class="my-4">

                <p class="text-center mb-0">
                    Já tem conta?
                    <a href="<?= url('/login') ?>" class="btn btn-sm btn-bazar ms-1">Entrar</a>
                </p>
            </div>
        </div>
    </div>
</div>
