<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Gerencia a conexão com o MySQL usando o padrão Singleton:
 * a conexão PDO é criada apenas na primeira chamada e reaproveitada depois.
 */
final class Database
{
    /** Instância única da conexão (null até a primeira chamada). */
    private static ?PDO $conexao = null;

    /** Construtor privado: ninguém pode fazer "new Database()". */
    private function __construct()
    {
    }

    /** Impede clonar a instância. */
    private function __clone()
    {
    }

    /**
     * Retorna a conexão PDO, criando-a se ainda não existir.
     *
     * @throws PDOException quando não for possível conectar
     */
    public static function getConexao(): PDO
    {
        if (self::$conexao === null) {
            /** @var array{host:string,dbname:string,user:string,pass:string,charset:string} $config */
            $config = require dirname(__DIR__, 2) . '/config/database.php';

            // DSN = "Data Source Name": diz ao PDO qual driver e banco usar
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['dbname'],
                $config['charset']
            );

            $opcoes = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // erros viram exceções
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // resultados como array associativo
                PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statements reais no MySQL
            ];

            try {
                self::$conexao = new PDO($dsn, $config['user'], $config['pass'], $opcoes);

                // Mesmo fuso do PHP no MySQL: senão CURRENT_TIMESTAMP (criado_em) sai em UTC
                // na VM do Google Cloud, enquanto o PHP grava em horário de Brasília.
                // date('P') devolve o deslocamento atual, ex.: "-03:00".
                if (self::$conexao->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                    self::$conexao->exec('SET time_zone = ' . self::$conexao->quote(date('P')));
                }
            } catch (PDOException $e) {
                // Registra o detalhe técnico no log do servidor e repassa uma mensagem genérica,
                // para não expor usuário/host do banco na tela.
                error_log('[Database] Falha na conexão: ' . $e->getMessage());
                throw new PDOException('Não foi possível conectar ao banco de dados.', (int) $e->getCode());
            }
        }

        return self::$conexao;
    }
}
