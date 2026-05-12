-- =============================================================
-- SIGE - Script 21: Vinculo de despesa de pessoal com lideres
-- =============================================================
-- Objetivo:
-- 1) Permitir referencia explicita de lider em despesas
-- 2) Incluir subcategoria salarial de lider nas categorias permitidas
-- 3) Atualizar views de despesas e rastreabilidade
-- =============================================================

SET search_path TO sige, public;

ALTER TABLE sige.despesas_campanha
    ADD COLUMN IF NOT EXISTS lider_referencia_id UUID DEFAULT NULL;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'fk_despesas_campanha_lider_referencia'
    ) THEN
        ALTER TABLE sige.despesas_campanha
            ADD CONSTRAINT fk_despesas_campanha_lider_referencia
            FOREIGN KEY (lider_referencia_id)
            REFERENCES sige.lideres(id)
            ON DELETE SET NULL
            ON UPDATE CASCADE;
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS ix_despesas_campanha_lider_referencia
    ON sige.despesas_campanha(lider_referencia_id)
    WHERE excluido_em IS NULL;

INSERT INTO sige.categorias_permitidas_recurso (tipo_recurso, categoria, subcategoria, ativo)
SELECT tipos.tipo_recurso, 'equipe_campanha', 'salario_lider', TRUE
FROM (
    VALUES
        ('fundo_partidario'::sige.tipo_recurso_campanha_enum),
        ('fundo_eleitoral'::sige.tipo_recurso_campanha_enum),
        ('doacao_privada'::sige.tipo_recurso_campanha_enum)
) AS tipos(tipo_recurso)
ON CONFLICT (tipo_recurso, categoria, subcategoria)
DO UPDATE SET
    ativo = EXCLUDED.ativo,
    atualizado_em = NOW();

DROP VIEW IF EXISTS sige.vw_financeiro_rastreabilidade CASCADE;
DROP VIEW IF EXISTS sige.vw_financeiro_despesas CASCADE;

CREATE VIEW sige.vw_financeiro_despesas
    WITH (security_barrier = true)
AS
SELECT
    d.id,
    d.candidato_id,
    l.nome AS candidato_nome,
    d.receita_id,
    r.tipo_recurso::text AS tipo_recurso,
    r.origem AS receita_origem,
    d.fornecedor_id,
    f.nome AS fornecedor_nome,
    d.categoria,
    d.subcategoria,
    d.lider_referencia_id,
    lr.nome AS lider_referencia_nome,
    d.valor,
    d.data_despesa,
    d.descricao,
    d.classificacao_conformidade::text AS classificacao_conformidade,
    d.conformidade_motivo,
    d.desvio_padrao_percentual,
    d.criado_por_usuario_id,
    u.nome AS criado_por_usuario_nome,
    d.criado_em,
    d.atualizado_em
FROM sige.despesas_campanha d
INNER JOIN sige.lideres l
    ON l.id = d.candidato_id AND l.excluido_em IS NULL
INNER JOIN sige.receitas_campanha r
    ON r.id = d.receita_id AND r.excluido_em IS NULL
INNER JOIN sige.fornecedores_campanha f
    ON f.id = d.fornecedor_id AND f.excluido_em IS NULL
LEFT JOIN sige.lideres lr
    ON lr.id = d.lider_referencia_id AND lr.excluido_em IS NULL
LEFT JOIN sige.usuarios u
    ON u.id = d.criado_por_usuario_id AND u.excluido_em IS NULL
WHERE d.excluido_em IS NULL;

CREATE VIEW sige.vw_financeiro_rastreabilidade AS
SELECT
    d.id AS despesa_id,
    d.data_despesa,
    d.candidato_id,
    d.candidato_nome,
    d.categoria,
    d.subcategoria,
    d.lider_referencia_id,
    d.lider_referencia_nome,
    d.valor AS despesa_valor,
    d.classificacao_conformidade,
    d.conformidade_motivo,
    d.fornecedor_id,
    d.fornecedor_nome,
    d.receita_id,
    d.tipo_recurso,
    d.receita_origem,
    r.valor_total AS receita_valor_total,
    r.valor_disponivel AS receita_valor_disponivel_atual,
    r.data_recebimento AS receita_data_recebimento
FROM sige.vw_financeiro_despesas d
INNER JOIN sige.vw_financeiro_receitas r
    ON r.id = d.receita_id;
