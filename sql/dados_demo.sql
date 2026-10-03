-- =============================================================
-- Dados de DEMONSTRAÇÃO (opcional) para a apresentação.
-- Rode DEPOIS do schema.sql (ou das migrações).
--
-- Contas criadas (senha de todas: bazar123)
--   ana@unaerp.br    -> dona de doações e trocas
--   bruno@unaerp.br  -> interessado em itens da Ana
--   carla@unaerp.br  -> interessada e dona de um item
--
-- Não use estas contas em produção: apague-as depois da apresentação.
-- =============================================================

USE bazar_universitario;

-- Hash bcrypt de "bazar123" (gerado com password_hash)
INSERT IGNORE INTO usuarios (nome, email, senha_hash) VALUES
    ('Ana Souza',    'ana@unaerp.br',   '$2y$10$rGiaZ5JmGnJv3DKLk8DhXuZig3o.PyMqgBh/T76XrcR5h2aLsgbBW'),
    ('Bruno Lima',   'bruno@unaerp.br', '$2y$10$rGiaZ5JmGnJv3DKLk8DhXuZig3o.PyMqgBh/T76XrcR5h2aLsgbBW'),
    ('Carla Mendes', 'carla@unaerp.br', '$2y$10$rGiaZ5JmGnJv3DKLk8DhXuZig3o.PyMqgBh/T76XrcR5h2aLsgbBW');

-- Guarda os IDs em variáveis para não depender de AUTO_INCREMENT
SET @ana   := (SELECT id FROM usuarios WHERE email = 'ana@unaerp.br');
SET @bruno := (SELECT id FROM usuarios WHERE email = 'bruno@unaerp.br');
SET @carla := (SELECT id FROM usuarios WHERE email = 'carla@unaerp.br');

SET @livros    := (SELECT id FROM categorias WHERE nome = 'Livros');
SET @eletro    := (SELECT id FROM categorias WHERE nome = 'Eletrônicos');
SET @material  := (SELECT id FROM categorias WHERE nome = 'Material de Estudo');
SET @moveis    := (SELECT id FROM categorias WHERE nome = 'Móveis');
SET @outros    := (SELECT id FROM categorias WHERE nome = 'Outros');

-- Itens (sem foto: aparecem com o placeholder do tipo)
INSERT INTO itens (usuario_id, categoria_id, nome, descricao, tipo, status, concluido_em) VALUES
    (@ana,   @livros,   'Cálculo Vol. 1 - James Stewart', 'Edição 8, algumas marcações a lápis. Ótimo para Cálculo I.', 'doacao', 'disponivel', NULL),
    (@ana,   @eletro,   'Mouse sem fio Logitech',          'Funciona perfeitamente, acompanha pilha. Troco por um mousepad grande.', 'troca', 'disponivel', NULL),
    (@ana,   @material, 'Jaleco branco tamanho M',         'Usado em um semestre de laboratório, lavado.', 'doacao', 'reservado', NULL),
    (@ana,   @moveis,   'Luminária de mesa',               'Lâmpada LED inclusa.', 'doacao', 'concluido', NOW()),
    (@carla, @material, 'Calculadora científica Casio',    'Modelo fx-82MS. Troco por um livro de Física.', 'troca', 'disponivel', NULL),
    (@carla, @outros,   'Garrafa térmica 1L',              'Nova, ganhei duas de presente.', 'doacao', 'disponivel', NULL);

-- Interesses
SET @calculo := (SELECT id FROM itens WHERE nome = 'Cálculo Vol. 1 - James Stewart' AND usuario_id = @ana LIMIT 1);
SET @mouse   := (SELECT id FROM itens WHERE nome = 'Mouse sem fio Logitech'         AND usuario_id = @ana LIMIT 1);
SET @jaleco  := (SELECT id FROM itens WHERE nome = 'Jaleco branco tamanho M'        AND usuario_id = @ana LIMIT 1);

INSERT IGNORE INTO interesses (item_id, usuario_id) VALUES
    (@calculo, @bruno),
    (@calculo, @carla),
    (@mouse,   @bruno),
    (@jaleco,  @carla);
