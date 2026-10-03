<?php
/**
 * Formulário de edição (só chega aqui quem é o dono — ver ItemController::carregarItemDoDono).
 * Variáveis: $item (Item), $categorias, $old (dados atuais ou digitados)
 */
$mostrarStatus = true;
?>
<div class="row justify-content-center">
    <div class="col-xl-10">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('/dashboard') ?>">Meu painel</a></li>
                <li class="breadcrumb-item">
                    <a href="<?= url('/itens/detalhes?id=' . $item->getId()) ?>"><?= e($item->getNome()) ?></a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
            </ol>
        </nav>

        <div class="card card-bazar p-2 p-md-3">
            <div class="card-body">
                <h1 class="h3 fw-bold text-azul mb-4"><i class="bi bi-pencil-square"></i> Editar item</h1>

                <!-- POST /itens/editar -> ItemController@atualizar -->
                <form action="<?= url('/itens/editar') ?>" method="post" enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $item->getId() ?>">

                    <?php require __DIR__ . '/_form.php'; ?>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="<?= url('/itens/detalhes?id=' . $item->getId()) ?>" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-bazar">
                            <i class="bi bi-check2"></i> Salvar alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
