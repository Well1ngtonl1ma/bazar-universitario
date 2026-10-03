<?php
/**
 * Campos do formulário de item, compartilhados por criar.php e editar.php.
 * O <form> que inclui este arquivo PRECISA ter enctype="multipart/form-data",
 * senão o navegador não envia o arquivo.
 *
 * Variáveis: $categorias, $old (valores atuais), $mostrarStatus (bool),
 *            $item (?Item, só na edição: foto atual e status concluído)
 */

use App\Core\Upload;
use App\Model\Item;

$itemAtual   = $item ?? null;
$tipoAtual   = (string) ($old['tipo'] ?? 'doacao');
$statusAtual = (string) ($old['status'] ?? 'disponivel');

// Pré-visualização inicial: foto atual (edição) ou placeholder do tipo
$srcPreview = $itemAtual !== null
    ? url($itemAtual->getImagemCaminho())
    : url('/assets/img/placeholder-' . ($tipoAtual === 'troca' ? 'troca' : 'doacao') . '.svg');
?>
<div class="row g-4">
    <!-- Coluna dos textos -->
    <div class="col-md-7">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome do item <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nome" name="nome" maxlength="120" required autofocus
                   placeholder="Ex.: Livro Cálculo Vol. 1 - Stewart" value="<?= e((string) ($old['nome'] ?? '')) ?>">
        </div>

        <div class="mb-3">
            <label for="categoria_id" class="form-label">Categoria <span class="text-danger">*</span></label>
            <select class="form-select" id="categoria_id" name="categoria_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= (int) $categoria['id'] ?>"
                        <?= (int) $categoria['id'] === (int) ($old['categoria_id'] ?? 0) ? 'selected' : '' ?>>
                        <?= e($categoria['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Tipo: decide qual subclasse (ItemDoacao / ItemTroca) o item vira -->
        <fieldset class="mb-3">
            <legend class="form-label fs-6">Tipo de anúncio <span class="text-danger">*</span></legend>
            <div class="row g-2">
                <div class="col-sm-6">
                    <input type="radio" class="btn-check" name="tipo" id="tipo_doacao" value="doacao"
                           <?= $tipoAtual === 'doacao' ? 'checked' : '' ?> required>
                    <label class="btn btn-outline-warning text-start w-100 p-3 text-dark" for="tipo_doacao">
                        <i class="bi bi-gift"></i> <strong>Doação</strong><br>
                        <small>Entrego de graça para quem precisar.</small>
                    </label>
                </div>
                <div class="col-sm-6">
                    <input type="radio" class="btn-check" name="tipo" id="tipo_troca" value="troca"
                           <?= $tipoAtual === 'troca' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-primary text-start w-100 p-3" for="tipo_troca">
                        <i class="bi bi-arrow-left-right"></i> <strong>Troca</strong><br>
                        <small>Quero outro item em troca.</small>
                    </label>
                </div>
            </div>
        </fieldset>

        <div class="mb-3">
            <label for="descricao" class="form-label">Descrição</label>
            <textarea class="form-control" id="descricao" name="descricao" rows="5" maxlength="2000"
                      placeholder="Estado de conservação, edição, o que você aceita em troca..."><?= e((string) ($old['descricao'] ?? '')) ?></textarea>
            <div class="form-text">Opcional. Até 2000 caracteres.</div>
        </div>

        <?php if (!empty($mostrarStatus)): ?>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <?php if ($itemAtual !== null && $itemAtual->estaConcluido()): ?>
                    <!-- Concluído só muda pelo botão "Reabrir" do painel -->
                    <input type="text" class="form-control" id="status" value="Concluído em <?= e($itemAtual->getConcluidoEmFormatado()) ?>" disabled>
                    <div class="form-text">Para devolver o item à vitrine, use "Reabrir" no painel.</div>
                <?php else: ?>
                    <select class="form-select" id="status" name="status">
                        <?php foreach (Item::STATUS_EDITAVEIS as $valor => $rotulo): ?>
                            <option value="<?= e($valor) ?>" <?= $valor === $statusAtual ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">"Reservado" tira o item da vitrine enquanto você combina a entrega.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Coluna da foto -->
    <div class="col-md-5">
        <label for="imagem" class="form-label">Foto do item</label>
        <div class="text-center mb-2">
            <img id="preview-imagem" src="<?= $srcPreview ?>" class="preview-upload" alt="Pré-visualização da foto">
        </div>

        <!-- MAX_FILE_SIZE: o PHP recusa antes de gravar o temporário (o limite real é checado no servidor) -->
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) Upload::TAMANHO_MAXIMO ?>">
        <input type="file" class="form-control" id="imagem" name="imagem" accept="<?= e(Upload::ACCEPT) ?>">
        <div class="form-text">Opcional. JPG, PNG ou WEBP, até 2 MB. Sem foto, usamos uma imagem padrão.</div>
        <div id="aviso-imagem" class="alert alert-warning py-2 small mt-2 d-none" role="alert"></div>

        <?php if ($itemAtual !== null && $itemAtual->temFoto()): ?>
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="remover_imagem" name="remover_imagem" value="1">
                <label class="form-check-label" for="remover_imagem">Remover a foto atual (usar imagem padrão)</label>
            </div>
        <?php endif; ?>
    </div>
</div>
