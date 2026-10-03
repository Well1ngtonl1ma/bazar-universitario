-- =============================================================
-- Bazar Universitário - Script de criação do banco (versão final, Etapa 3)
--
-- Instalação nova ............ rode este arquivo.
-- Banco da Etapa 1 ........... rode migracao_etapa2.sql e depois migracao_etapa3.sql.
-- Banco da Etapa 2 ........... rode apenas migracao_etapa3.sql.
-- Dados para demonstração .... depois, rode dados_demo.sql (opcional).
--
-- Fluxo: cria o banco -> tabelas independentes (usuarios, categorias)
--        -> tabelas dependentes (itens, interesses) -> dados iniciais
-- =============================================================

CREATE DATABASE IF NOT EXISTS bazar_universitario
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bazar_universitario;

-- -------------------------------------------------------------
-- Tabela: usuarios
-- Guarda apenas o HASH da senha (nunca a senha em texto puro).
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    senha_hash  VARCHAR(255) NOT NULL,          -- 255 suporta futuros algoritmos do PASSWORD_DEFAULT
    criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_usuarios_email UNIQUE (email) -- impede e-mails duplicados no nível do banco
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Tabela: categorias
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome  VARCHAR(60) NOT NULL,
    CONSTRAINT uq_categorias_nome UNIQUE (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Tabela: itens (anúncios publicados pelos usuários)
-- A coluna `tipo` decide qual subclasse PHP representa a linha:
--   'doacao' -> App\Model\ItemDoacao   |   'troca' -> App\Model\ItemTroca
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS itens (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id     INT UNSIGNED NOT NULL,
    categoria_id   INT UNSIGNED NOT NULL,
    nome           VARCHAR(120) NOT NULL,
    descricao      TEXT NULL,
    tipo           ENUM('doacao', 'troca') NOT NULL DEFAULT 'doacao',
    imagem         VARCHAR(255) NULL,                     -- só o NOME do arquivo em public/uploads/ (NULL = placeholder)
    status         ENUM('disponivel', 'reservado', 'concluido') NOT NULL DEFAULT 'disponivel',
    concluido_em   DATETIME NULL,                         -- quando foi doado/trocado
    criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Se o usuário for excluído, seus anúncios também são.
    CONSTRAINT fk_itens_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE,
    -- Não permite excluir uma categoria que ainda tem itens.
    CONSTRAINT fk_itens_categoria FOREIGN KEY (categoria_id)
        REFERENCES categorias (id) ON DELETE RESTRICT ON UPDATE CASCADE,

    -- Índices para as consultas mais comuns (vitrine, filtros, painel)
    INDEX idx_itens_status (status),
    INDEX idx_itens_tipo (tipo),
    INDEX idx_itens_criado_em (criado_em),
    INDEX idx_itens_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Tabela: interesses (usuário demonstra interesse em um item)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS interesses (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id     INT UNSIGNED NOT NULL,
    usuario_id  INT UNSIGNED NOT NULL,
    mensagem    VARCHAR(500) NULL,
    criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_interesses_item FOREIGN KEY (item_id)
        REFERENCES itens (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_interesses_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE,

    -- Um mesmo usuário só registra interesse uma vez por item
    CONSTRAINT uq_interesse_item_usuario UNIQUE (item_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Dados iniciais: categorias padrão
-- INSERT IGNORE evita erro caso o script seja executado novamente.
-- -------------------------------------------------------------
INSERT IGNORE INTO categorias (nome) VALUES
    ('Livros'),
    ('Eletrônicos'),
    ('Material de Estudo'),
    ('Móveis'),
    ('Outros');
