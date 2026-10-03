<?php

declare(strict_types=1);

namespace App\Model;

use App\Core\Database;
use DateTimeImmutable;
use PDO;
use PDOException;
use UnexpectedValueException;

/**
 * CLASSE BASE ABSTRATA de um item do bazar.
 *
 * POO na apresentação:
 *  - HERANÇA: ItemDoacao e ItemTroca herdam atributos e métodos daqui.
 *  - ABSTRAÇÃO: "Item" sozinho não existe; todo item é doação OU troca,
 *    por isso a classe é abstract e não pode ser instanciada.
 *  - POLIMORFISMO: as views chamam $item->getBadgeTipo() ou getImagemCaminho()
 *    sem saber a subclasse; cada uma responde do seu jeito.
 *  - ENCAPSULAMENTO: atributos protected, acesso só por getters.
 *
 * Persistência: métodos estáticos com PDO + Prepared Statements.
 * Eles devolvem OBJETOS (ItemDoacao/ItemTroca), não arrays — ver criarObjeto().
 */
abstract class Item
{
    /** Tipos aceitos (valor no banco => rótulo na tela). */
    public const TIPOS = [
        'doacao' => 'Doação',
        'troca'  => 'Troca',
    ];

    /** Todos os status (valor no banco => rótulo na tela). */
    public const STATUS = [
        'disponivel' => 'Disponível',
        'reservado'  => 'Reservado',
        'concluido'  => 'Concluído',
    ];

    /**
     * Status que o dono escolhe no formulário de edição.
     * "Concluído" tem botão próprio, porque grava a data e tira o item da vitrine.
     */
    public const STATUS_EDITAVEIS = [
        'disponivel' => 'Disponível',
        'reservado'  => 'Reservado',
    ];

    /** SELECT padrão com JOINs (categoria e dono) e contagem de interessados. */
    private const SQL_SELECT = '
        SELECT i.id, i.usuario_id, i.categoria_id, i.nome, i.descricao, i.tipo, i.imagem,
               i.status, i.concluido_em, i.criado_em,
               c.nome  AS categoria_nome,
               u.nome  AS usuario_nome,
               u.email AS usuario_email,
               (SELECT COUNT(*) FROM interesses x WHERE x.item_id = i.id) AS total_interesses
          FROM itens i
          INNER JOIN categorias c ON c.id = i.categoria_id
          INNER JOIN usuarios   u ON u.id = i.usuario_id';

    // ---------------------------------------------------------
    // Atributos (protected: visíveis nas subclasses, não fora delas)
    // ---------------------------------------------------------
    protected int $id;
    protected int $usuarioId;
    protected int $categoriaId;
    protected string $nome;
    protected ?string $descricao;
    protected string $tipo;            // cada subclasse define o seu valor
    protected ?string $imagem;         // nome do arquivo em public/uploads (null = sem foto)
    protected string $status;
    protected ?string $concluidoEm;
    protected string $criadoEm;

    // Dados vindos dos JOINs / subconsulta
    protected string $categoriaNome;
    protected string $usuarioNome;
    protected string $usuarioEmail;
    protected int $totalInteresses;

    /**
     * Monta o objeto a partir de uma linha do banco (array associativo).
     *
     * @param array<string, mixed> $linha
     */
    public function __construct(array $linha)
    {
        $this->id              = (int) $linha['id'];
        $this->usuarioId       = (int) $linha['usuario_id'];
        $this->categoriaId     = (int) $linha['categoria_id'];
        $this->nome            = (string) $linha['nome'];
        $this->descricao       = $linha['descricao'] !== null ? (string) $linha['descricao'] : null;
        $this->imagem          = !empty($linha['imagem']) ? (string) $linha['imagem'] : null;
        $this->status          = (string) $linha['status'];
        $this->concluidoEm     = !empty($linha['concluido_em']) ? (string) $linha['concluido_em'] : null;
        $this->criadoEm        = (string) $linha['criado_em'];
        $this->categoriaNome   = (string) ($linha['categoria_nome'] ?? '');
        $this->usuarioNome     = (string) ($linha['usuario_nome'] ?? '');
        $this->usuarioEmail    = (string) ($linha['usuario_email'] ?? '');
        $this->totalInteresses = (int) ($linha['total_interesses'] ?? 0);
        // $tipo NÃO vem da linha: é fixado pela subclasse (ItemDoacao = 'doacao').
    }

    // =========================================================
    // MÉTODOS ABSTRATOS: cada subclasse é obrigada a implementar
    // =========================================================

    /** @return array{rotulo:string, classe:string, icone:string} Dados do badge do tipo. */
    abstract public function getBadgeTipo(): array;

    /** Texto do botão de interesse ("Tenho interesse em receber" / "... em trocar"). */
    abstract public function getMensagemAcao(): string;

    /** Frase curta explicando o tipo. */
    abstract public function getDescricaoTipo(): string;

    /** @return list<string> Regras de negócio exibidas na página de detalhes. */
    abstract public function getRegras(): array;

    /** Imagem padrão quando não há foto (caminho relativo a public/). */
    abstract protected function getPlaceholder(): string;

    /** Verbo do botão de conclusão ("Marcar como doado" / "Marcar como trocado"). */
    abstract public function getRotuloConclusao(): string;

    // =========================================================
    // Getters e regras simples (herdados por todas as subclasses)
    // =========================================================

    public function getId(): int { return $this->id; }
    public function getUsuarioId(): int { return $this->usuarioId; }
    public function getCategoriaId(): int { return $this->categoriaId; }
    public function getNome(): string { return $this->nome; }
    public function getDescricao(): ?string { return $this->descricao; }
    public function getTipo(): string { return $this->tipo; }
    public function getImagem(): ?string { return $this->imagem; }
    public function getStatus(): string { return $this->status; }
    public function getCategoriaNome(): string { return $this->categoriaNome; }
    public function getUsuarioNome(): string { return $this->usuarioNome; }
    public function getUsuarioEmail(): string { return $this->usuarioEmail; }
    public function getTotalInteresses(): int { return $this->totalInteresses; }

    public function temFoto(): bool
    {
        return $this->imagem !== null;
    }

    /**
     * Caminho (relativo a public/) da imagem a exibir: a foto enviada
     * ou o placeholder da subclasse. A view só faz url($item->getImagemCaminho()).
     */
    public function getImagemCaminho(): string
    {
        return $this->temFoto() ? 'uploads/' . $this->imagem : $this->getPlaceholder();
    }

    public function getStatusRotulo(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    /** Classe de cor do Bootstrap para o status. */
    public function getStatusClasse(): string
    {
        return match ($this->status) {
            'disponivel' => 'bg-success',
            'reservado'  => 'bg-warning text-dark',
            'concluido'  => 'bg-secondary',
            default      => 'bg-light text-dark',
        };
    }

    public function estaDisponivel(): bool
    {
        return $this->status === 'disponivel';
    }

    public function estaConcluido(): bool
    {
        return $this->status === 'concluido';
    }

    /** REGRA DE POSSE: o item pertence ao usuário informado? */
    public function pertenceA(?int $usuarioId): bool
    {
        return $usuarioId !== null && $usuarioId === $this->usuarioId;
    }

    /** "2026-10-02 21:44:00" -> "02/10/2026" */
    public function getCriadoEmFormatado(): string
    {
        return (new DateTimeImmutable($this->criadoEm))->format('d/m/Y');
    }

    public function getConcluidoEmFormatado(): ?string
    {
        return $this->concluidoEm !== null
            ? (new DateTimeImmutable($this->concluidoEm))->format('d/m/Y')
            : null;
    }

    /** Resumo da descrição para os cards da vitrine. */
    public function getResumo(int $limite = 110): string
    {
        $texto = trim($this->descricao ?? '');
        return mb_strlen($texto) > $limite ? mb_substr($texto, 0, $limite) . '…' : $texto;
    }

    // =========================================================
    // FÁBRICA: decide a subclasse a partir da coluna `tipo`
    // =========================================================

    /** @param array<string, mixed> $linha */
    public static function criarObjeto(array $linha): Item
    {
        return match ($linha['tipo']) {
            'doacao' => new ItemDoacao($linha),
            'troca'  => new ItemTroca($linha),
            default  => throw new UnexpectedValueException("Tipo de item desconhecido: {$linha['tipo']}"),
        };
    }

    // =========================================================
    // VALIDAÇÃO dos dados de formulário (regras do domínio)
    // =========================================================

    /**
     * @param array<string, mixed> $dados
     * @return list<string> Lista de erros (vazia = válido)
     */
    public static function validar(array $dados, bool $validarStatus = false): array
    {
        $erros = [];
        $nome  = trim((string) ($dados['nome'] ?? ''));

        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 120) {
            $erros[] = 'O nome do item deve ter entre 3 e 120 caracteres.';
        }
        if (mb_strlen((string) ($dados['descricao'] ?? '')) > 2000) {
            $erros[] = 'A descrição pode ter no máximo 2000 caracteres.';
        }
        if (!array_key_exists((string) ($dados['tipo'] ?? ''), self::TIPOS)) {
            $erros[] = 'Escolha se o item é para doação ou troca.';
        }
        if ((int) ($dados['categoria_id'] ?? 0) <= 0) {
            $erros[] = 'Escolha uma categoria.';
        }
        if ($validarStatus && !array_key_exists((string) ($dados['status'] ?? ''), self::STATUS)) {
            $erros[] = 'Status inválido.';
        }

        return $erros;
    }

    // =========================================================
    // PERSISTÊNCIA (PDO + Prepared Statements)
    // =========================================================

    private static function db(): PDO
    {
        return Database::getConexao();
    }

    /**
     * Vitrine pública: SOMENTE itens disponíveis (concluídos e reservados ficam de fora).
     *
     * @return list<Item>
     */
    public static function listarDisponiveis(?int $categoriaId = null): array
    {
        $sql    = self::SQL_SELECT . " WHERE i.status = 'disponivel'";
        $params = [];

        if ($categoriaId !== null) {
            $sql .= ' AND i.categoria_id = :categoria_id';
            $params[':categoria_id'] = $categoriaId;
        }
        $sql .= ' ORDER BY i.criado_em DESC, i.id DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        // Cada linha vira ItemDoacao ou ItemTroca
        return array_map([self::class, 'criarObjeto'], $stmt->fetchAll());
    }

    /**
     * Anúncios de um usuário (painel). Ativos primeiro, concluídos depois.
     *
     * @return list<Item>
     */
    public static function listarPorUsuario(int $usuarioId): array
    {
        $sql = self::SQL_SELECT . "
             WHERE i.usuario_id = :usuario_id
             ORDER BY CASE WHEN i.status = 'concluido' THEN 1 ELSE 0 END,
                      COALESCE(i.concluido_em, i.criado_em) DESC, i.id DESC";

        $stmt = self::db()->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);

        return array_map([self::class, 'criarObjeto'], $stmt->fetchAll());
    }

    /** Busca um item com os dados da categoria e do proprietário. */
    public static function buscarPorId(int $id): ?Item
    {
        $stmt = self::db()->prepare(self::SQL_SELECT . ' WHERE i.id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);

        $linha = $stmt->fetch();
        return $linha ? self::criarObjeto($linha) : null;
    }

    /**
     * INSERT de um novo item (sempre começa como 'disponivel').
     *
     * @param array{usuario_id:int, categoria_id:int, nome:string, descricao:?string, tipo:string, imagem:?string} $dados
     * @return int ID gerado
     */
    public static function salvar(array $dados): int
    {
        $sql = 'INSERT INTO itens (usuario_id, categoria_id, nome, descricao, tipo, imagem, status)
                VALUES (:usuario_id, :categoria_id, :nome, :descricao, :tipo, :imagem, \'disponivel\')';

        $stmt = self::db()->prepare($sql);
        $stmt->execute([
            ':usuario_id'   => $dados['usuario_id'],
            ':categoria_id' => $dados['categoria_id'],
            ':nome'         => trim($dados['nome']),
            ':descricao'    => self::descricaoOuNull($dados['descricao'] ?? null),
            ':tipo'         => $dados['tipo'],
            ':imagem'       => $dados['imagem'] ?? null,
        ]);

        return (int) self::db()->lastInsertId();
    }

    /**
     * UPDATE validando a posse.
     * Mesmo que o controller já tenha checado, o WHERE com usuario_id é
     * uma segunda barreira: sem ser dono, nenhuma linha é alterada.
     *
     * @param array{categoria_id:int, nome:string, descricao:?string, tipo:string, status:string, imagem:?string} $dados
     * @return bool false se o item não existir ou não for do usuário
     */
    public static function atualizar(int $id, array $dados, int $usuarioId): bool
    {
        if (!self::ehDono($id, $usuarioId)) {
            return false;
        }

        $sql = 'UPDATE itens
                   SET categoria_id = :categoria_id, nome = :nome, descricao = :descricao,
                       tipo = :tipo, status = :status, imagem = :imagem
                 WHERE id = :id AND usuario_id = :usuario_id';

        $stmt = self::db()->prepare($sql);
        $stmt->execute([
            ':categoria_id' => $dados['categoria_id'],
            ':nome'         => trim($dados['nome']),
            ':descricao'    => self::descricaoOuNull($dados['descricao'] ?? null),
            ':tipo'         => $dados['tipo'],
            ':status'       => $dados['status'],
            ':imagem'       => $dados['imagem'] ?? null,
            ':id'           => $id,
            ':usuario_id'   => $usuarioId,
        ]);

        return true;
    }

    /**
     * Marca o item como CONCLUÍDO (doado/trocado) e grava a data.
     * Some da vitrine automaticamente, pois listarDisponiveis() só traz 'disponivel'.
     */
    public static function concluir(int $id, int $usuarioId): bool
    {
        return self::mudarStatus($id, $usuarioId, 'concluido', date('Y-m-d H:i:s'));
    }

    /** Desfaz a conclusão (ex.: a entrega não aconteceu) e o item volta para a vitrine. */
    public static function reabrir(int $id, int $usuarioId): bool
    {
        return self::mudarStatus($id, $usuarioId, 'disponivel', null);
    }

    /**
     * DELETE validando a posse. Os interesses do item são apagados
     * automaticamente pelo ON DELETE CASCADE da chave estrangeira.
     * (A foto é apagada do disco pelo controller.)
     */
    public static function deletar(int $id, int $usuarioId): bool
    {
        $stmt = self::db()->prepare('DELETE FROM itens WHERE id = :id AND usuario_id = :usuario_id');
        $stmt->execute([':id' => $id, ':usuario_id' => $usuarioId]);

        return $stmt->rowCount() > 0;
    }

    // ---------------------------------------------------------
    // Painel do usuário
    // ---------------------------------------------------------

    /**
     * Indicadores do dashboard em duas consultas agregadas.
     *
     * @return array{total:int, disponiveis:int, reservados:int, concluidos:int, interesses_recebidos:int}
     */
    public static function estatisticasDoUsuario(int $usuarioId): array
    {
        // CASE WHEN ... funciona igual no MySQL e no MariaDB
        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(CASE WHEN status = 'disponivel' THEN 1 ELSE 0 END), 0) AS disponiveis,
                       COALESCE(SUM(CASE WHEN status = 'reservado'  THEN 1 ELSE 0 END), 0) AS reservados,
                       COALESCE(SUM(CASE WHEN status = 'concluido'  THEN 1 ELSE 0 END), 0) AS concluidos
                  FROM itens
                 WHERE usuario_id = :usuario_id";

        $stmt = self::db()->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        $itens = $stmt->fetch() ?: [];

        // Interesses recebidos = manifestações em itens DESTE usuário
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) FROM interesses it
              INNER JOIN itens i ON i.id = it.item_id
              WHERE i.usuario_id = :usuario_id'
        );
        $stmt->execute([':usuario_id' => $usuarioId]);

        return [
            'total'                => (int) ($itens['total'] ?? 0),
            'disponiveis'          => (int) ($itens['disponiveis'] ?? 0),
            'reservados'           => (int) ($itens['reservados'] ?? 0),
            'concluidos'           => (int) ($itens['concluidos'] ?? 0),
            'interesses_recebidos' => (int) $stmt->fetchColumn(),
        ];
    }

    /**
     * Últimos interesses recebidos nos itens do usuário (feed do painel).
     *
     * @return list<array{item_id:int, item_nome:string, nome:string, email:string, criado_em:string}>
     */
    public static function listarInteressesRecebidos(int $usuarioId, int $limite = 5): array
    {
        $sql = 'SELECT i.id AS item_id, i.nome AS item_nome, u.nome, u.email, it.criado_em
                  FROM interesses it
                  INNER JOIN itens    i ON i.id = it.item_id
                  INNER JOIN usuarios u ON u.id = it.usuario_id
                 WHERE i.usuario_id = :usuario_id
                 ORDER BY it.criado_em DESC, it.id DESC
                 LIMIT :limite';

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);   // LIMIT exige inteiro
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ---------------------------------------------------------
    // Interesses
    // ---------------------------------------------------------

    /**
     * Registra o interesse. A UNIQUE (item_id, usuario_id) do banco impede duplicidade;
     * se o usuário clicar duas vezes, capturamos o erro 23000 e devolvemos false.
     *
     * @return bool true = registrado agora | false = já existia
     */
    public static function registrarInteresse(int $itemId, int $usuarioId): bool
    {
        try {
            $stmt = self::db()->prepare('INSERT INTO interesses (item_id, usuario_id) VALUES (:item_id, :usuario_id)');
            $stmt->execute([':item_id' => $itemId, ':usuario_id' => $usuarioId]);
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Lista para o proprietário combinar a entrega.
     *
     * @return list<array{nome:string, email:string, criado_em:string}>
     */
    public static function listarInteressados(int $itemId): array
    {
        $sql = 'SELECT u.nome, u.email, it.criado_em
                  FROM interesses it
                  INNER JOIN usuarios u ON u.id = it.usuario_id
                 WHERE it.item_id = :item_id
                 ORDER BY it.criado_em ASC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute([':item_id' => $itemId]);
        return $stmt->fetchAll();
    }

    public static function usuarioTemInteresse(int $itemId, int $usuarioId): bool
    {
        $stmt = self::db()->prepare('SELECT 1 FROM interesses WHERE item_id = :item_id AND usuario_id = :usuario_id');
        $stmt->execute([':item_id' => $itemId, ':usuario_id' => $usuarioId]);
        return $stmt->fetchColumn() !== false;
    }

    // ---------------------------------------------------------
    // Auxiliares privados
    // ---------------------------------------------------------

    private static function mudarStatus(int $id, int $usuarioId, string $status, ?string $concluidoEm): bool
    {
        if (!self::ehDono($id, $usuarioId)) {
            return false;
        }
        $stmt = self::db()->prepare(
            'UPDATE itens SET status = :status, concluido_em = :concluido_em
              WHERE id = :id AND usuario_id = :usuario_id'
        );
        $stmt->execute([
            ':status'       => $status,
            ':concluido_em' => $concluidoEm,
            ':id'           => $id,
            ':usuario_id'   => $usuarioId,
        ]);
        return true;
    }

    private static function ehDono(int $itemId, int $usuarioId): bool
    {
        $stmt = self::db()->prepare('SELECT 1 FROM itens WHERE id = :id AND usuario_id = :usuario_id');
        $stmt->execute([':id' => $itemId, ':usuario_id' => $usuarioId]);
        return $stmt->fetchColumn() !== false;
    }

    /** Descrição vazia é gravada como NULL. */
    private static function descricaoOuNull(?string $descricao): ?string
    {
        $descricao = trim($descricao ?? '');
        return $descricao === '' ? null : $descricao;
    }
}
