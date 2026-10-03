-- =============================================================
-- Migração Etapa 2 -> Etapa 3
-- Compatível com MySQL 5.7+/8 e MariaDB 10.4 (XAMPP).
-- Pode ser executada mais de uma vez sem erro (cada passo verifica antes).
--
-- O que muda na tabela `itens`:
--   - garante a coluna imagem VARCHAR(255) NULL (foto do item)
--   - nova coluna concluido_em (data em que foi doado/trocado)
--   - status 'finalizado' passa a se chamar 'concluido'
-- =============================================================

USE bazar_universitario;

-- -------------------------------------------------------------
-- 1) Coluna `imagem` (só cria se ainda não existir)
--    MySQL não tem "ADD COLUMN IF NOT EXISTS", então consultamos o
--    information_schema e montamos o comando dinamicamente.
-- -------------------------------------------------------------
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens' AND COLUMN_NAME = 'imagem');
SET @sql := IF(@existe = 0,
    'ALTER TABLE itens ADD COLUMN imagem VARCHAR(255) NULL AFTER tipo',
    'DO 0');   -- comando vazio quando a coluna já existe
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -------------------------------------------------------------
-- 2) Coluna `concluido_em`
-- -------------------------------------------------------------
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens' AND COLUMN_NAME = 'concluido_em');
SET @sql := IF(@existe = 0,
    'ALTER TABLE itens ADD COLUMN concluido_em DATETIME NULL AFTER status',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -------------------------------------------------------------
-- 3) Status: 'finalizado' -> 'concluido' (três passos)
-- -------------------------------------------------------------
-- (a) enum temporário aceitando os dois nomes
ALTER TABLE itens
    MODIFY COLUMN status ENUM('disponivel', 'reservado', 'finalizado', 'concluido') NOT NULL DEFAULT 'disponivel';

-- (b) converte os registros antigos (data de conclusão = última alteração)
UPDATE itens
   SET status = 'concluido',
       concluido_em = COALESCE(concluido_em, atualizado_em)
 WHERE status = 'finalizado';

-- (c) enum definitivo
ALTER TABLE itens
    MODIFY COLUMN status ENUM('disponivel', 'reservado', 'concluido') NOT NULL DEFAULT 'disponivel';
