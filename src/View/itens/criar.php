<?php
/**
 * Formulário de novo anúncio.
 * Variáveis: $categorias, $old
 */
$mostrarStatus = false; // item novo sempre nasce "disponivel"
$item = null;           // não há item ainda (o _form usa para foto/status)
?>
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card card-bazar destaque p-2 p-md-3">
            <div class="card-body">
                <h1 class="h3 fw-bold text-azul mb-1"><i class="bi bi-plus-circle"></i> Anunciar item</h1>
                <p class="text-muted mb-4">Preencha os dados do item que você quer doar ou trocar.</p>

                <!-- POST /itens/criar -> ItemController@salvar
                     enctype multipart é obrigatório para enviar arquivos -->
                <form action="<?= url('/itens/criar') ?>" method="post" enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>

                    <?php require __DIR__ . '/_form.php'; ?>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="<?= url('/') ?>" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-ouro">
                            <i class="bi bi-megaphone"></i> Publicar anúncio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
