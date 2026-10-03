<?php
/**
 * Tela de login.
 * Variáveis: $old (valores digitados antes de um erro)
 */
?>
<div class="row justify-content-center">
    <div class="col-sm-10 col-md-7 col-lg-5">
        <div class="card card-bazar p-2 p-md-3">
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="bi bi-person-lock fs-1 text-azul"></i>
                    <h1 class="h3 fw-bold text-azul mt-2">Entrar</h1>
                    <p class="text-muted mb-0">Acesse sua conta do Bazar</p>
                </div>

                <!-- POST /login -> AuthController@autenticar -->
                <form action="<?= url('/login') ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= e($old['email'] ?? '') ?>" required autofocus autocomplete="email">
                    </div>

                    <div class="mb-4">
                        <label for="senha" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="senha" name="senha"
                               required autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn btn-bazar w-100 py-2">
                        <i class="bi bi-box-arrow-in-right"></i> Entrar
                    </button>
                </form>

                <hr class="my-4">

                <p class="text-center mb-0">
                    Ainda não tem conta?
                    <a href="<?= url('/cadastro') ?>" class="btn btn-sm btn-ouro ms-1">Cadastre-se</a>
                </p>
            </div>
        </div>
    </div>
</div>
