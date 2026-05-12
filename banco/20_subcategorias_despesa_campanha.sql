-- =============================================================
-- SIGE - Script 20: Subcategorias de despesas de campanha
-- =============================================================
-- Objetivo:
-- 1) Adicionar subcategoria em despesas
-- 2) Ajustar cadastro de categorias permitidas para categoria+subcategoria
-- 3) Atualizar views operacionais com subcategoria
-- =============================================================

SET search_path TO sige, public;

ALTER TABLE sige.despesas_campanha
    ADD COLUMN IF NOT EXISTS subcategoria VARCHAR(80);

UPDATE sige.despesas_campanha
SET subcategoria = COALESCE(NULLIF(TRIM(subcategoria), ''), 'geral')
WHERE subcategoria IS NULL OR TRIM(subcategoria) = '';

ALTER TABLE sige.despesas_campanha
    ALTER COLUMN subcategoria SET NOT NULL;

ALTER TABLE sige.categorias_permitidas_recurso
    ADD COLUMN IF NOT EXISTS subcategoria VARCHAR(80);

UPDATE sige.categorias_permitidas_recurso
SET subcategoria = COALESCE(NULLIF(TRIM(subcategoria), ''), 'geral')
WHERE subcategoria IS NULL OR TRIM(subcategoria) = '';

ALTER TABLE sige.categorias_permitidas_recurso
    ALTER COLUMN subcategoria SET NOT NULL;

ALTER TABLE sige.categorias_permitidas_recurso
    DROP CONSTRAINT IF EXISTS pk_categorias_permitidas_recurso;

ALTER TABLE sige.categorias_permitidas_recurso
    ADD CONSTRAINT pk_categorias_permitidas_recurso
    PRIMARY KEY (tipo_recurso, categoria, subcategoria);

INSERT INTO sige.categorias_permitidas_recurso (tipo_recurso, categoria, subcategoria, ativo)
SELECT tipos.tipo_recurso, cat.categoria, cat.subcategoria, TRUE
FROM (
    VALUES
        ('fundo_partidario'::sige.tipo_recurso_campanha_enum),
        ('fundo_eleitoral'::sige.tipo_recurso_campanha_enum),
        ('doacao_privada'::sige.tipo_recurso_campanha_enum)
) AS tipos(tipo_recurso)
CROSS JOIN (
    VALUES
        ('material_grafico', 'santinhos'),
        ('material_grafico', 'panfletos'),
        ('material_grafico', 'adesivos'),
        ('material_grafico', 'bandeiras'),
        ('material_grafico', 'cartazes'),

        ('producao_conteudo', 'gravacao_videos'),
        ('producao_conteudo', 'fotografia_profissional'),
        ('producao_conteudo', 'edicao_video'),
        ('producao_conteudo', 'design_grafico'),

        ('marketing_digital', 'gestao_redes_sociais'),
        ('marketing_digital', 'trafego_pago'),
        ('marketing_digital', 'impulsionamento_posts'),

        ('equipe_campanha', 'coordenador_geral'),
        ('equipe_campanha', 'cabos_eleitorais'),
        ('equipe_campanha', 'equipe_rua'),
        ('equipe_campanha', 'social_media'),
        ('equipe_campanha', 'designers'),
        ('equipe_campanha', 'advogado_eleitoral'),
        ('equipe_campanha', 'contador'),

        ('transporte_logistica', 'combustivel'),
        ('transporte_logistica', 'aluguel_veiculos'),
        ('transporte_logistica', 'motoristas'),
        ('transporte_logistica', 'manutencao_basica'),

        ('eventos_mobilizacao', 'comicios'),
        ('eventos_mobilizacao', 'reunioes_comunitarias'),
        ('eventos_mobilizacao', 'aluguel_espaco'),
        ('eventos_mobilizacao', 'som_estrutura'),
        ('eventos_mobilizacao', 'alimentacao_eventos'),

        ('servicos_terceiros', 'graficas'),
        ('servicos_terceiros', 'empresas_marketing'),
        ('servicos_terceiros', 'pesquisas_eleitorais'),
        ('servicos_terceiros', 'consultorias'),

        ('comunicacao_oficial', 'jingles'),
        ('comunicacao_oficial', 'programas_radio_tv'),
        ('comunicacao_oficial', 'assessoria_imprensa'),

        ('custos_juridicos_contabeis', 'prestacao_contas'),
        ('custos_juridicos_contabeis', 'acompanhamento_juridico')
) AS cat(categoria, subcategoria)
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
