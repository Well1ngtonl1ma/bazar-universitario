<?php
/**
 * Detalhes do item.
 * Variáveis: $item (ItemDoacao|ItemTroca), $ehDono, $logado, $interessados, $jaTemInteresse
 *
 * O painel da direita muda conforme quem está vendo:
 *   visitante -> convite para entrar
 *   logado    -> botão "Tenho interesse" (ou aviso de interesse já registrado)
 *   dono      -> concluir / editar / excluir + lista de interessados
 */
$badge = $item->getBadgeTipo();
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
        <?php if ($ehDono): ?>
            <li class="breadcrumb-item"><a href="<?= url('/dashboard') ?>">Meu painel</a></li>
        <?php else: ?>
            <li class="breadcrumb-item"><a href="<?= url('/') ?>">Vitrine</a></li>
            <li class="breadcrumb-item">
                <a href="<?= url('/?categoria=' . $item->getCategoriaId()) ?>"><?= e($item->getCategoriaNome()) ?></a>
            </li>
        <?php endif; ?>
        <li class="breadcrumb-item active" aria-current="page"><?= e($item->getNome()) ?></li>
    </ol>
</nav>

<?php if ($item->estaConcluido()): ?>
    <div class="alert alert-secondary">
        <i class="bi bi-archive"></i>
        Item concluído em <?= e($item->getConcluidoEmFormatado()) ?>. Ele não aparece na vitrine e só você consegue vê-lo.
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Coluna principal: foto e dados do item -->
    <div class="col-lg-8">
        <div class="card card-bazar<?= $item->getTipo() === 'doacao' ? ' destaque' : '' ?>">
            <div class="card-body p-4">
                <img src="<?= url($item->getImagemCaminho()) ?>" class="foto-detalhe mb-4"
                     alt="<?= e($item->temFoto() ? 'Foto de ' . $item->getNome() : 'Item sem foto') ?>">

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge <?= e($badge['classe']) ?> fs-6">
                        <i class="bi <?= e($badge['icone']) ?>"></i> <?= e($badge['rotulo']) ?>
                    </span>
                    <span class="badge <?= e($item->getStatusClasse()) ?> fs-6"><?= e($item->getStatusRotulo()) ?></span>
                </div>

                <h1 class="h2 fw-bold text-azul"><?= e($item->getNome()) ?></h1>
                <p class="text-muted">
                    <i class="bi bi-tag"></i> <?= e($item->getCategoriaNome()) ?>
                    &middot; <i class="bi bi-person"></i> Anunciado por <?= e($item->getUsuarioNome()) ?>
                    &middot; <i class="bi bi-calendar3"></i> <?= e($item->getCriadoEmFormatado()) ?>
                </p>

                <hr>

                <h2 class="h6 text-uppercase text-muted">Descrição</h2>
                <?php if ($item->getDescricao()): ?>
                    <!-- e() escapa o HTML; nl2br() só depois, para manter as quebras de linha -->
                    <p class="mb-0"><?= nl2br(e($item->getDescricao())) ?></p>
                <?php else: ?>
                    <p class="text-muted fst-italic mb-0">O anunciante não adicionou uma descrição.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Regras do tipo: texto vem da subclasse (polimorfismo) -->
        <div class="card card-bazar mt-4">
            <div class="card-body p-4">
                <h2 class="h5 text-azul"><i class="bi <?= e($badge['icone']) ?>"></i> Como funciona: <?= e($badge['rotulo']) ?></h2>
                <p class="text-muted"><?= e($item->getDescricaoTipo()) ?></p>
                <ul class="mb-0">
                    <?php foreach ($item->getRegras() as $regra): ?>
                        <li><?= e($regra) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Coluna lateral: ações -->
    <div class="col-lg-4">
        <?php if ($ehDono): ?>
            <!-- ===== DONO ===== -->
            <div class="card card-bazar destaque mb-4">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Seu anúncio</h2>
                    <div class="d-grid gap-2">
                        <?php if ($item->estaConcluido()): ?>
                            <form action="<?= url('/itens/reabrir') ?>" method="post"
                                  data-confirmar="Devolver este item para a vitrine?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $item->getId() ?>">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reabrir anúncio
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Rótulo vem da subclasse: "Marcar como doado" / "Marcar como trocado" -->
                            <form action="<?= url('/itens/concluir') ?>" method="post"
                                  data-confirmar="Confirmar que o item já foi entregue? Ele sairá da vitrine.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $item->getId() ?>">
                                <button type="submit" class="btn btn-ouro w-100">
                                    <i class="bi bi-check2-circle"></i> <?= e($item->getRotuloConclusao()) ?>
                                </button>
                            </form>
                        <?php endif; ?>

                        <a href="<?= url('/itens/editar?id=' . $item->getId()) ?>" class="btn btn-bazar">
                            <i class="bi bi-pencil-square"></i> Editar
                        </a>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalExcluir">
                            <i class="bi bi-trash"></i> Excluir
                        </button>
                    </div>
                </div>
            </div>

            <div class="card card-bazar">
                <div class="card-body">
                    <h2 class="h5 text-azul">
                        <i class="bi bi-people"></i> Interessados
                        <span class="badge badge-ouro"><?= (int) count($interessados) ?></span>
                    </h2>

                    <?php if ($interessados === []): ?>
                        <p class="text-muted mb-0">Ninguém manifestou interesse ainda.</p>
                    <?php else: ?>
                        <p class="small text-muted">Entre em contato por e-mail para combinar a entrega.</p>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr><th>Nome</th><th>E-mail</th><th>Data</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($interessados as $interessado): ?>
                                        <tr>
                                            <td><?= e($interessado['nome']) ?></td>
                                            <td class="text-break">
                                                <a href="mailto:<?= e($interessado['email']) ?>?subject=<?= e(rawurlencode('Bazar Universitário: ' . $item->getNome())) ?>">
                                                    <?= e($interessado['email']) ?>
                                                </a>
                                            </td>
                                            <td class="small text-muted"><?= e(date('d/m', strtotime($interessado['criado_em']))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal de confirmação da exclusão -->
            <div class="modal fade" id="modalExcluir" tabindex="-1" aria-labelledby="modalExcluirTitulo" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title h5" id="modalExcluirTitulo">Excluir item</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            Tem certeza que deseja excluir <strong><?= e($item->getNome()) ?></strong>?
                            A foto e os interesses registrados também serão apagados. Essa ação não pode ser desfeita.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <!-- POST /itens/deletar -> ItemController@deletar -->
                            <form action="<?= url('/itens/deletar') ?>" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $item->getId() ?>">
                                <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Excluir</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif (!$logado): ?>
            <!-- ===== VISITANTE ===== -->
            <div class="card card-bazar">
                <div class="card-body text-center">
                    <i class="bi bi-lock fs-2 text-azul"></i>
                    <p class="mt-2">Entre na sua conta para manifestar interesse neste item.</p>
                    <a href="<?= url('/login') ?>" class="btn btn-bazar w-100 mb-2">Entrar</a>
                    <a href="<?= url('/cadastro') ?>" class="btn btn-ouro w-100">Criar conta</a>
                </div>
            </div>

        <?php elseif ($jaTemInteresse): ?>
            <!-- ===== LOGADO, JÁ DEMONSTROU INTERESSE ===== -->
            <div class="card card-bazar">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle-fill fs-2 text-success"></i>
                    <h2 class="h5 mt-2">Interesse registrado</h2>
                    <p class="text-muted mb-0">
                        <?= e($item->getUsuarioNome()) ?> já pode ver seu nome e e-mail.
                        Fique de olho na sua caixa de entrada.
                    </p>
                </div>
            </div>

        <?php elseif ($item->estaDisponivel()): ?>
            <!-- ===== LOGADO, PODE MANIFESTAR INTERESSE ===== -->
            <div class="card card-bazar destaque">
                <div class="card-body">
                    <h2 class="h5 text-azul">Gostou deste item?</h2>
                    <p class="small text-muted">
                        Ao clicar, o anunciante recebe seu nome e e-mail para combinar a entrega.
                    </p>
                    <!-- POST /itens/interesse -> ItemController@manifestarInteresse -->
                    <form action="<?= url('/itens/interesse') ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $item->getId() ?>">
                        <button type="submit" class="btn btn-ouro w-100 py-2">
                            <i class="bi bi-hand-thumbs-up"></i> <?= e($item->getMensagemAcao()) ?>
                        </button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <!-- ===== ITEM RESERVADO ===== -->
            <div class="card card-bazar">
                <div class="card-body text-center">
                    <i class="bi bi-hourglass-split fs-2 text-muted"></i>
                    <p class="mt-2 mb-0 text-muted">Este item está <?= e(mb_strtolower($item->getStatusRotulo())) ?> e não recebe novos interessados.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
