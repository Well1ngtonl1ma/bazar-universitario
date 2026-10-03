<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Core\Upload;
use App\Model\Categoria;
use App\Model\Item;
use RuntimeException;
use Throwable;

/**
 * CRUD de itens, foto, conclusão, interesse e painel do usuário.
 *
 * Regras de acesso:
 *  - Detalhes: público (item concluído: só o dono vê).
 *  - Criar, painel e interesse: só logado.
 *  - Editar/Atualizar/Deletar/Concluir/Reabrir: só o DONO.
 *
 * O ID do item chega pela query string (?id=5) nos GET
 * e por campo oculto nos POST, pois o Router compara rotas exatas.
 */
class ItemController extends Controller
{
    /** GET /itens/criar */
    public function criar(): void
    {
        $this->exigirLogin();

        $this->render('itens/criar', [
            'titulo'     => 'Anunciar item',
            'categorias' => Categoria::listarTodas(),
            'old'        => $this->pegarOld(),
        ]);
    }

    /** POST /itens/criar (multipart/form-data) */
    public function salvar(): void
    {
        $this->exigirLogin();
        $this->validarCsrf();

        // 1) Valida os campos de texto ANTES de mexer em arquivos
        $dados = $this->dadosDoFormulario();
        $erros = $this->validarFormulario($dados);
        if ($erros !== []) {
            $this->voltarComErro('/itens/criar', implode(' ', $erros), $dados);
        }

        // 2) Foto opcional: sem arquivo = placeholder
        $upload = Upload::pastaPadrao();
        $dados['imagem'] = $this->processarUpload($upload, '/itens/criar', $dados);

        // 3) O dono vem da SESSÃO, nunca do formulário
        $dados['usuario_id'] = $this->idUsuarioLogado();

        try {
            $id = Item::salvar($dados);
        } catch (Throwable $e) {
            $upload->remover($dados['imagem']); // não deixa foto órfã se o INSERT falhar
            throw $e;
        }

        $this->flash('success', 'Item anunciado com sucesso!');
        $this->redirect('/itens/detalhes?id=' . $id);
    }

    /** GET /itens/detalhes?id=X */
    public function detalhes(): void
    {
        $item      = $this->buscarItemOuVoltar($this->lerId($_GET));
        $usuarioId = $this->idUsuarioLogado();
        $ehDono    = $item->pertenceA($usuarioId);

        // Concluído sai do ar: só o dono acessa, pelo histórico do painel
        if ($item->estaConcluido() && !$ehDono) {
            $this->flash('info', 'Este item já foi ' . ($item->getTipo() === 'doacao' ? 'doado' : 'trocado') . ' e não está mais disponível.');
            $this->redirect('/');
        }

        $this->render('itens/detalhes', [
            'titulo'         => $item->getNome(),
            'item'           => $item,
            'ehDono'         => $ehDono,
            'logado'         => $usuarioId !== null,
            'interessados'   => $ehDono ? Item::listarInteressados($item->getId()) : [],
            'jaTemInteresse' => ($usuarioId !== null && !$ehDono)
                ? Item::usuarioTemInteresse($item->getId(), $usuarioId)
                : false,
        ]);
    }

    /** GET /itens/editar?id=X (só o dono) */
    public function editar(): void
    {
        $this->exigirLogin();
        $item = $this->carregarItemDoDono($this->lerId($_GET));

        // Se voltou de um erro, usa o que foi digitado; senão, os dados do banco
        $old = $this->pegarOld() ?: [
            'nome'         => $item->getNome(),
            'descricao'    => $item->getDescricao() ?? '',
            'categoria_id' => $item->getCategoriaId(),
            'tipo'         => $item->getTipo(),
            'status'       => $item->getStatus(),
        ];

        $this->render('itens/editar', [
            'titulo'     => 'Editar item',
            'item'       => $item,
            'categorias' => Categoria::listarTodas(),
            'old'        => $old,
        ]);
    }

    /** POST /itens/editar (só o dono, multipart/form-data) */
    public function atualizar(): void
    {
        $this->exigirLogin();
        $this->validarCsrf();

        $item   = $this->carregarItemDoDono($this->lerId($_POST));
        $voltar = '/itens/editar?id=' . $item->getId();

        // 1) Campos de texto + status
        $dados = $this->dadosDoFormulario();
        $erros = $this->validarFormulario($dados);

        if ($item->estaConcluido()) {
            // Concluído só muda pelo botão "Reabrir"; a edição preserva o status e a data
            $dados['status'] = 'concluido';
        } else {
            $dados['status'] = (string) ($_POST['status'] ?? '');
            if (!array_key_exists($dados['status'], Item::STATUS_EDITAVEIS)) {
                $erros[] = 'Status inválido.';
            }
        }

        if ($erros !== []) {
            $this->voltarComErro($voltar, implode(' ', $erros), $dados);
        }

        // 2) Foto: nova (substitui), remover (volta ao placeholder) ou manter
        $upload     = Upload::pastaPadrao();
        $novaImagem = $this->processarUpload($upload, $voltar, $dados);
        $removerFoto = !empty($_POST['remover_imagem']);

        $imagemAntiga    = $item->getImagem();
        $dados['imagem'] = $novaImagem ?? ($removerFoto ? null : $imagemAntiga);

        // 3) UPDATE (o Model confere a posse de novo: defesa em profundidade)
        try {
            $ok = Item::atualizar($item->getId(), $dados, $this->idUsuarioLogado());
        } catch (Throwable $e) {
            $upload->remover($novaImagem);
            throw $e;
        }
        if (!$ok) {
            $upload->remover($novaImagem);
            $this->negarAcesso($item->getId());
        }

        // 4) Só apaga a foto antiga DEPOIS que o banco foi atualizado
        if ($imagemAntiga !== null && $imagemAntiga !== $dados['imagem']) {
            $upload->remover($imagemAntiga);
        }

        $this->flash('success', 'Item atualizado com sucesso.');
        $this->redirect('/itens/detalhes?id=' . $item->getId());
    }

    /** POST /itens/deletar (só o dono) */
    public function deletar(): void
    {
        $this->exigirLogin();
        $this->validarCsrf();

        $item = $this->carregarItemDoDono($this->lerId($_POST));

        if (!Item::deletar($item->getId(), $this->idUsuarioLogado())) {
            $this->negarAcesso($item->getId());
        }
        Upload::pastaPadrao()->remover($item->getImagem());

        $this->flash('success', 'O item "' . $item->getNome() . '" foi removido.');
        $this->redirect('/dashboard');
    }

    /** POST /itens/concluir (só o dono): marca como doado/trocado */
    public function concluir(): void
    {
        $this->exigirLogin();
        $this->validarCsrf();

        $item = $this->carregarItemDoDono($this->lerId($_POST));

        if ($item->estaConcluido()) {
            $this->flash('info', 'Este item já estava concluído.');
        } elseif (Item::concluir($item->getId(), $this->idUsuarioLogado())) {
            $verbo = $item->getTipo() === 'doacao' ? 'doado' : 'trocado';
            $this->flash('success', "\"{$item->getNome()}\" foi marcado como {$verbo}. Ele saiu da vitrine e está no seu histórico.");
        } else {
            $this->negarAcesso($item->getId());
        }
        $this->redirect('/dashboard');
    }

    /** POST /itens/reabrir (só o dono): desfaz a conclusão */
    public function reabrir(): void
    {
        $this->exigirLogin();
        $this->validarCsrf();

        $item = $this->carregarItemDoDono($this->lerId($_POST));

        if (!$item->estaConcluido()) {
            $this->flash('info', 'Este item não está concluído.');
        } elseif (Item::reabrir($item->getId(), $this->idUsuarioLogado())) {
            $this->flash('success', "\"{$item->getNome()}\" voltou para a vitrine.");
        } else {
            $this->negarAcesso($item->getId());
        }
        $this->redirect('/dashboard');
    }

    /** POST /itens/interesse */
    public function manifestarInteresse(): void
    {
        $this->exigirLogin();
        $this->validarCsrf();

        $item      = $this->buscarItemOuVoltar($this->lerId($_POST));
        $usuarioId = $this->idUsuarioLogado();
        $voltar    = '/itens/detalhes?id=' . $item->getId();

        // Regras de negócio
        if ($item->pertenceA($usuarioId)) {
            $this->flash('warning', 'Você não pode manifestar interesse no seu próprio item.');
            $this->redirect($voltar);
        }
        if (!$item->estaDisponivel()) {
            $this->flash('warning', 'Este item não está mais disponível.');
            $this->redirect($item->estaConcluido() ? '/' : $voltar);
        }

        if (Item::registrarInteresse($item->getId(), $usuarioId)) {
            $this->flash('success', 'Interesse registrado! O anunciante verá seu nome e e-mail para combinar a entrega.');
        } else {
            $this->flash('info', 'Você já havia manifestado interesse neste item.');
        }
        $this->redirect($voltar);
    }

    /** GET /dashboard: painel com indicadores, anúncios ativos e histórico */
    public function dashboard(): void
    {
        $this->exigirLogin();
        $usuarioId = $this->idUsuarioLogado();

        $itens = Item::listarPorUsuario($usuarioId);

        $this->render('dashboard', [
            'titulo'        => 'Meu painel',
            'estatisticas'  => Item::estatisticasDoUsuario($usuarioId),
            // array_values reindexa depois do filtro
            'ativos'        => array_values(array_filter($itens, fn (Item $i) => !$i->estaConcluido())),
            'concluidos'    => array_values(array_filter($itens, fn (Item $i) => $i->estaConcluido())),
            'interessesRecentes' => Item::listarInteressesRecebidos($usuarioId, 5),
            'nomeUsuario'   => (string) ($_SESSION['usuario_nome'] ?? ''),
        ]);
    }

    /** GET /meus-itens: endereço antigo (Etapa 2), agora redireciona para o painel */
    public function meusItens(): void
    {
        $this->redirect('/dashboard');
    }

    // =========================================================
    // Auxiliares privados
    // =========================================================

    /**
     * Salva a foto se o usuário escolheu uma. Se ela for inválida,
     * volta ao formulário com a mensagem do Upload (os textos digitados são mantidos).
     *
     * @param array<string, mixed> $old
     * @return string|null Nome do arquivo salvo, ou null se nenhuma foto foi enviada
     */
    private function processarUpload(Upload $upload, string $rotaErro, array $old): ?string
    {
        $arquivo = $_FILES['imagem'] ?? null;
        if (!Upload::foiEnviado($arquivo)) {
            return null;
        }

        try {
            return $upload->salvar($arquivo);
        } catch (RuntimeException $e) {
            $this->voltarComErro($rotaErro, $e->getMessage() . ' Selecione a foto novamente.', $old);
        }
    }

    /**
     * Lê o "id" de $_GET ou $_POST como inteiro positivo (0 se inválido).
     *
     * @param array<string, mixed> $origem
     */
    private function lerId(array $origem): int
    {
        $id = filter_var($origem['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? 0 : $id;
    }

    /** Busca o item; se não existir, volta para a vitrine com aviso. */
    private function buscarItemOuVoltar(int $id): Item
    {
        $item = $id > 0 ? Item::buscarPorId($id) : null;

        if ($item === null) {
            $this->flash('warning', 'Item não encontrado.');
            $this->redirect('/');
        }
        return $item;
    }

    /**
     * REGRA DE SEGURANÇA: só devolve o item se o usuário logado for o dono.
     * Protege editar/atualizar/deletar/concluir mesmo que alguém digite a URL ou forje o POST.
     */
    private function carregarItemDoDono(int $id): Item
    {
        $item = $this->buscarItemOuVoltar($id);

        if (!$item->pertenceA($this->idUsuarioLogado())) {
            $this->negarAcesso($item->getId());
        }
        return $item;
    }

    private function negarAcesso(int $itemId): never
    {
        $this->flash('danger', 'Acesso negado: apenas quem anunciou pode alterar ou remover este item.');
        $this->redirect('/itens/detalhes?id=' . $itemId);
    }

    /** @return array{nome:string, descricao:string, categoria_id:int, tipo:string} */
    private function dadosDoFormulario(): array
    {
        return [
            'nome'         => trim((string) ($_POST['nome'] ?? '')),
            'descricao'    => trim((string) ($_POST['descricao'] ?? '')),
            'categoria_id' => (int) ($_POST['categoria_id'] ?? 0),
            'tipo'         => (string) ($_POST['tipo'] ?? ''),
        ];
    }

    /**
     * Regras do Model + checagem de que a categoria existe no banco.
     *
     * @param array<string, mixed> $dados
     * @return list<string>
     */
    private function validarFormulario(array $dados): array
    {
        $erros = Item::validar($dados);

        if ($dados['categoria_id'] > 0 && !Categoria::existe($dados['categoria_id'])) {
            $erros[] = 'Categoria inválida.';
        }
        return $erros;
    }
}
