<?php

declare(strict_types=1);

namespace App\Model;

use App\Core\Database;
use InvalidArgumentException;
use PDO;
use PDOException;

/**
 * Model de usuários: única camada que conversa com a tabela `usuarios`.
 * Todas as consultas usam Prepared Statements (proteção contra SQL Injection).
 */
class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexao();
    }

    /**
     * Cadastra um novo usuário.
     *
     * @return int ID do usuário criado
     * @throws InvalidArgumentException se o e-mail já estiver em uso
     */
    public function criar(string $nome, string $email, string $senha): int
    {
        $email = mb_strtolower(trim($email));

        // 1) Verificação amigável de duplicidade
        if ($this->buscarPorEmail($email) !== null) {
            throw new InvalidArgumentException('Este e-mail já está cadastrado.');
        }

        // 2) Gera o hash (inclui salt aleatório automaticamente)
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = 'INSERT INTO usuarios (nome, email, senha_hash) VALUES (:nome, :email, :senha_hash)';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nome'       => trim($nome),
                ':email'      => $email,
                ':senha_hash' => $senhaHash,
            ]);
        } catch (PDOException $e) {
            // 23000 = violação de restrição (UNIQUE). Cobre o caso raro de dois
            // cadastros simultâneos com o mesmo e-mail passarem pelo passo 1.
            if ($e->getCode() === '23000') {
                throw new InvalidArgumentException('Este e-mail já está cadastrado.');
            }
            throw $e;
        }

        return (int) $this->db->lastInsertId();
    }

    /**
     * Busca o usuário completo (inclui senha_hash) — usado no login.
     *
     * @return array{id:int,nome:string,email:string,senha_hash:string,criado_em:string}|null
     */
    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nome, email, senha_hash, criado_em FROM usuarios WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => mb_strtolower(trim($email))]);

        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }

    /**
     * Busca dados básicos (SEM o hash da senha) — usado para exibir perfil.
     *
     * @return array{id:int,nome:string,email:string,criado_em:string}|null
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nome, email, criado_em FROM usuarios WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);

        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }
}
