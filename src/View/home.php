<?php
/**
 * Vitrine pública: SOMENTE itens disponíveis (concluídos ficam no painel do dono).
 * Variáveis: $itens (list<Item>), $categorias, $categoriaId (?int), $categoriaNome (?string),
 *            $bancoConectado (bool), $logado (bool)
 *
 * Polimorfismo: o loop não pergunta "é doação ou troca?". Chama getBadgeTipo()
 * e getImagemCaminho(), e cada subclasse responde do seu jeito (inclusive o placeholder).
 */
?>
<!-- Faixa de boas-vindas -->
<div class="faixa-azul p-4 mb-4 rounded-4 text-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Bazar <span class="brand-destaque">Universitário</span></h1>
        <p class="mb-0 opacity-75">Doe e troque livros, eletrônicos e materiais com outros estudantes.</p>
    </div>
    <?php if ($logado): ?>
        <a href="<?= url('/itens/criar') ?>" class="btn btn-ouro btn-lg flex-shrink-0">
            <i class="bi bi-plus-circle"></i> Anunciar item
        </a>
    <?php else: ?>
        <a href="<?= url('/cadastro') ?>" class="btn btn-ouro btn-lg flex-shrink-0">Criar conta e anunciar</a>
    <?php endif; ?>
</div>

<?php if (!$bancoConectado): ?>
    <div class="alert alert-danger">
        <i class="bi bi-database-x"></i>
        Não foi possível carregar os itens. Verifique se o MySQL está ligado e se o banco foi criado.
    </div>
<?php else: ?>

    <!-- Filtro por categoria: links GET com ?categoria=X -->
    <nav class="filtro-categoria d-flex flex-wrap gap-2 mb-4" aria-label="Filtrar por categoria">
        <a href="<?= url('/') ?>" class="btn btn-sm btn-filtro<?= $categoriaId === null ? ' ativo' : '' ?>">Todas</a>
        <?php foreach ($categorias as $categoria): ?>
            <a href="<?= url('/?categoria=' . (int) $categoria['id']) ?>"
               class="btn btn-sm btn-filtro<?= (int) $categoria['id'] === $categoriaId ? ' ativo' : '' ?>">
                <?= e($categoria['nome']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="d-flex justify-content-between align-items-baseline mb-3">
        <h2 class="h5 text-azul mb-0">
            <?= $categoriaNome !== null ? e($categoriaNome) : 'Todos os itens disponíveis' ?>
        </h2>
        <span class="text-muted small"><?= (int) count($itens) ?> item(ns)</span>
    </div>

    <?php if ($itens === []): ?>
        <!-- Estado vazio -->
        <div class="card card-bazar text-center py-5">
            <div class="card-body">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-2 mb-3 text-muted">Nenhum item disponível <?= $categoriaNome !== null ? 'nesta categoria' : 'ainda' ?>.</p>
                <?php if ($logado): ?>
                    <a href="<?= url('/itens/criar') ?>" class="btn btn-bazar">Seja o primeiro a anunciar</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>

        <!-- Grid de cards -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
            <?php foreach ($itens as $item): ?>
                <?php $badge = $item->getBadgeTipo(); ?>
                <div class="col">
                    <div class="card card-bazar card-item h-100<?= $item->getTipo() === 'doacao' ? ' destaque' : '' ?>">
                        <a href="<?= url('/itens/detalhes?id=' . $item->getId()) ?>" class="foto-wrapper d-block">
                            <!-- Foto enviada ou placeholder do tipo; loading="lazy" só carrega ao rolar -->
                            <img src="<?= url($item->getImagemCaminho()) ?>" class="foto-item"
                                 alt="<?= e($item->temFoto() ? 'Foto de ' . $item->getNome() : 'Item sem foto') ?>" loading="lazy">
                            <span class="badge <?= e($badge['classe']) ?> shadow-sm">
                                <i class="bi <?= e($badge['icone']) ?>"></i> <?= e($badge['rotulo']) ?>
                            </span>
                        </a>

                        <div class="card-body d-flex flex-column">
                            <h3 class="card-title h5 mb-1"><?= e($item->getNome()) ?></h3>
                            <p class="small text-muted mb-2">
                                <i class="bi bi-tag"></i> <?= e($item->getCategoriaNome()) ?>
                                &middot;
                                <i class="bi bi-calendar3"></i> <?= e($item->getCriadoEmFormatado()) ?>
                            </p>

                            <?php if ($item->getResumo() !== ''): ?>
                                <p class="card-text text-secondary small"><?= e($item->getResumo()) ?></p>
                            <?php endif; ?>

                            <div class="mt-auto pt-2 d-flex justify-content-between align-items-center">
                                <span class="small text-muted"><i class="bi bi-person"></i> <?= e($item->getUsuarioNome()) ?></span>
                                <a href="<?= url('/itens/detalhes?id=' . $item->getId()) ?>" class="btn btn-sm btn-bazar">
                                    Ver detalhes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
<?php endif; ?>
