-- =============================================================
-- Migração Etapa 1 -> Etapa 2 (rode UMA vez, no banco já existente)
-- Compatível com MySQL 5.7+/8 e MariaDB 10.4 (versão do XAMPP).
--
-- O que muda na tabela `itens`:
--   - titulo  -> nome
--   - novo campo tipo ('doacao' | 'troca')
--   - preco e condicao saem (o bazar agora é só doação/troca)
--   - status 'vendido' vira 'finalizado'
-- Usuários, categorias e interesses NÃO são alterados.
-- =============================================================

USE bazar_universitario;

-- 1) Renomeia a coluna e troca o índice correspondente
ALTER TABLE itens
    CHANGE COLUMN titulo nome VARCHAR(120) NOT NULL,
    DROP INDEX idx_itens_titulo,
    ADD INDEX idx_itens_nome (nome);

-- 2) Novo campo que define a subclasse PHP (ItemDoacao / ItemTroca)
ALTER TABLE itens
    ADD COLUMN tipo ENUM('doacao', 'troca') NOT NULL DEFAULT 'doacao' AFTER descricao,
    ADD INDEX idx_itens_tipo (tipo);

-- 3) Remove colunas que não fazem mais parte do domínio
ALTER TABLE itens
    DROP COLUMN preco,
    DROP COLUMN condicao;

-- 4) Status: 'vendido' -> 'finalizado' em três passos
--    (a) enum temporário aceitando os dois valores
ALTER TABLE itens
    MODIFY COLUMN status ENUM('disponivel', 'reservado', 'vendido', 'finalizado') NOT NULL DEFAULT 'disponivel';
--    (b) converte os registros antigos
UPDATE itens SET status = 'finalizado' WHERE status = 'vendido';
--    (c) enum definitivo
ALTER TABLE itens
    MODIFY COLUMN status ENUM('disponivel', 'reservado', 'finalizado') NOT NULL DEFAULT 'disponivel';
