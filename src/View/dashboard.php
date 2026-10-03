<?php
/**
 * Painel do usuário.
 * Variáveis:
 *   $estatisticas       array{total, disponiveis, reservados, concluidos, interesses_recebidos}
 *   $ativos             list<Item> (disponíveis e reservados)
 *   $concluidos         list<Item> (histórico: doados/trocados)
 *   $interessesRecentes list<array{item_id, item_nome, nome, email, criado_em}>
 *   $nomeUsuario        string
 */

/** Indicadores exibidos nos cards (rótulo, valor, ícone, classe de cor). */
$indicadores = [
    ['Itens anunciados',      $estatisticas['total'],                'bi-box-seam',      ''],
    ['Na vitrine agora',      $estatisticas['disponiveis'],          'bi-shop',          'kpi-sucesso'],
    ['Concluídos',            $estatisticas['concluidos'],           'bi-check2-circle', 'kpi-cinza'],
    ['Interesses recebidos',  $estatisticas['interesses_recebidos'], 'bi-people',        'kpi-ouro'],
];
?>
<!-- Cabeçalho do painel -->
<div class="faixa-azul p-4 mb-4 rounded-4 text-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Olá, <span class="brand-destaque"><?= e($nomeUsuario) ?></span>!</h1>
        <p class="mb-0 opacity-75">Acompanhe seus anúncios e quem demonstrou interesse.</p>
    </div>
    <!-- Ações rápidas -->
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="<?= url('/itens/criar') ?>" class="btn btn-ouro"><i class="bi bi-plus-circle"></i> Anunciar item</a>
        <a href="<?= url('/') ?>" class="btn btn-outline-light"><i class="bi bi-shop"></i> Ver vitrine</a>
    </div>
</div>

<!-- Indicadores (KPIs) -->
<div class="row row-cols-2 row-cols-lg-4 g-3 mb-4">
    <?php foreach ($indicadores as [$rotulo, $valor, $icone, $classe]): ?>
        <div class="col">
            <div class="card card-bazar kpi <?= e($classe) ?> h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-valor"><?= (int) $valor ?></div>
                        <div class="small text-muted mt-1"><?= e($rotulo) ?></div>
                    </div>
                    <i class="bi <?= e($icone) ?> kpi-icone text-azul"></i>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Anúncios: abas Ativos / Histórico -->
    <div class="col-lg-8">
        <div class="card card-bazar">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#aba-ativos" type="button" role="tab"
                                aria-controls="aba-ativos" aria-selected="true">
                            Ativos <span class="badge badge-azul"><?= (int) count($ativos) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#aba-historico" type="button" role="tab"
                                aria-controls="aba-historico" aria-selected="false">
                            Histórico <span class="badge bg-secondary"><?= (int) count($concluidos) ?></span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- ===== ATIVOS ===== -->
                    <div class="tab-pane fade show active" id="aba-ativos" role="tabpanel">
                        <?php if ($ativos === []): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox fs-1 text-muted"></i>
                                <p class="text-muted mt-2">Nenhum anúncio ativo.</p>
                                <a href="<?= url('/itens/criar') ?>" class="btn btn-bazar">Anunciar meu primeiro item</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Status</th>
                                            <th class="text-center d-none d-md-table-cell">Interessados</th>
                                            <th class="text-end">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ativos as $item): ?>
                                            <?php $badge = $item->getBadgeTipo(); ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="<?= url($item->getImagemCaminho()) ?>" class="foto-miniatura" alt="" loading="lazy">
                                                        <div>
                                                            <a href="<?= url('/itens/detalhes?id=' . $item->getId()) ?>" class="fw-semibold text-decoration-none text-azul">
                                                                <?= e($item->getNome()) ?>
                                                            </a>
                                                            <div class="small">
                                                                <span class="badge <?= e($badge['classe']) ?>"><?= e($badge['rotulo']) ?></span>
                                                                <span class="text-muted"><?= e($item->getCategoriaNome()) ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge <?= e($item->getStatusClasse()) ?>"><?= e($item->getStatusRotulo()) ?></span></td>
                                                <td class="text-center d-none d-md-table-cell">
                                                    <span class="badge badge-ouro"><?= (int) $item->getTotalInteresses() ?></span>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <form action="<?= url('/itens/concluir') ?>" method="post" class="d-inline"
                                                          data-confirmar="Confirmar que o item já foi entregue? Ele sairá da vitrine.">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="id" value="<?= (int) $item->getId() ?>">
                                                        <button type="submit" class="btn btn-sm btn-ouro" title="<?= e($item->getRotuloConclusao()) ?>">
                                                            <i class="bi bi-check2-circle"></i><span class="d-none d-xl-inline"> Concluir</span>
                                                        </button>
                                                    </form>
                                                    <a href="<?= url('/itens/editar?id=' . $item->getId()) ?>" class="btn btn-sm btn-bazar" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ===== HISTÓRICO (concluídos) ===== -->
                    <div class="tab-pane fade" id="aba-historico" role="tabpanel">
                        <?php if ($concluidos === []): ?>
                            <p class="text-muted text-center py-4 mb-0">
                                Quando você doar ou trocar um item, ele aparece aqui.
                            </p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Concluído em</th>
                                            <th class="text-end">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($concluidos as $item): ?>
                                            <?php $badge = $item->getBadgeTipo(); ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="<?= url($item->getImagemCaminho()) ?>" class="foto-miniatura opacity-75" alt="" loading="lazy">
                                                        <div>
                                                            <a href="<?= url('/itens/detalhes?id=' . $item->getId()) ?>" class="fw-semibold text-decoration-none text-secondary">
                                                                <?= e($item->getNome()) ?>
                                                            </a>
                                                            <div class="small"><span class="badge <?= e($badge['classe']) ?>"><?= e($badge['rotulo']) ?></span></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= e($item->getConcluidoEmFormatado() ?? '-') ?></td>
                                                <td class="text-end">
                                                    <form action="<?= url('/itens/reabrir') ?>" method="post" class="d-inline"
                                                          data-confirmar="Devolver este item para a vitrine?">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="id" value="<?= (int) $item->getId() ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-arrow-counterclockwise"></i> Reabrir
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimos interesses recebidos -->
    <div class="col-lg-4">
        <div class="card card-bazar destaque h-100">
            <div class="card-body">
                <h2 class="h5 text-azul"><i class="bi bi-bell"></i> Últimos interesses</h2>

                <?php if ($interessesRecentes === []): ?>
                    <p class="text-muted mb-0">Ninguém demonstrou interesse nos seus itens ainda.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($interessesRecentes as $interesse): ?>
                            <li class="list-group-item px-0">
                                <div class="fw-semibold"><?= e($interesse['nome']) ?></div>
                                <div class="small text-muted">
                                    quer <a href="<?= url('/itens/detalhes?id=' . (int) $interesse['item_id']) ?>"><?= e($interesse['item_nome']) ?></a>
                                </div>
                                <div class="small">
                                    <a href="mailto:<?= e($interesse['email']) ?>"><?= e($interesse['email']) ?></a>
                                    <span class="text-muted">&middot; <?= e(date('d/m H:i', strtotime($interesse['criado_em']))) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
