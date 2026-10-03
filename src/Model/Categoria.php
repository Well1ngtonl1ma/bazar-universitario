<?php

declare(strict_types=1);

namespace App\Model;

use App\Core\Database;

/**
 * Model de categorias (tabela `categorias`).
 * Métodos estáticos: categorias são só leitura nesta etapa, não precisamos de objeto.
 */
class Categoria
{
    /**
     * Todas as categorias em ordem alfabética, para selects e filtros.
     *
     * @return list<array{id:int,nome:string}>
     */
    public static function listarTodas(): array
    {
        $stmt = Database::getConexao()->query('SELECT id, nome FROM categorias ORDER BY nome');
        return $stmt->fetchAll();
    }

    /**
     * Confere se o ID recebido do formulário é de uma categoria real
     * (o usuário pode alterar o <select> pelo DevTools).
     */
    public static function existe(int $id): bool
    {
        $stmt = Database::getConexao()->prepare('SELECT 1 FROM categorias WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() !== false;
    }
}
