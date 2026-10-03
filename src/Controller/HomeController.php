<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Model\Categoria;
use App\Model\Item;
use PDOException;

/**
 * Vitrine pública: itens disponíveis com filtro por categoria (?categoria=X).
 *
 * Etapa 3: itens "concluido" (doados/trocados) e "reservado" NÃO aparecem aqui,
 * porque Item::listarDisponiveis() filtra status = 'disponivel' direto no SQL.
 * Eles continuam acessíveis ao dono no painel (/dashboard).
 */
class HomeController extends Controller
{
    /** GET / */
    public function index(): void
    {
        // 1) Lê o filtro da URL. Valor inválido ou ausente = "Todas".
        $categoriaId = filter_var($_GET['categoria'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $categoriaId = $categoriaId === false ? null : $categoriaId;

        $itens          = [];
        $categorias     = [];
        $bancoConectado = true;

        // 2) Busca categorias (para os botões) e itens (já filtrados no SQL)
        try {
            $categorias = Categoria::listarTodas();
            $itens      = Item::listarDisponiveis($categoriaId);
        } catch (PDOException) {
            // A página continua de pé e mostra um aviso no lugar da vitrine
            $bancoConectado = false;
        }

        // 3) Nome da categoria escolhida, para o título da listagem
        $categoriaNome = null;
        foreach ($categorias as $categoria) {
            if ((int) $categoria['id'] === $categoriaId) {
                $categoriaNome = $categoria['nome'];
                break;
            }
        }

        $this->render('home', [
            'titulo'         => 'Vitrine',
            'itens'          => $itens,
            'categorias'     => $categorias,
            'categoriaId'    => $categoriaId,
            'categoriaNome'  => $categoriaNome,
            'bancoConectado' => $bancoConectado,
            'logado'         => $this->usuarioLogado(),
        ]);
    }
}
