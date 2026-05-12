-- =============================================================
-- SIGE - Script 19: Categorias padrao de despesas de campanha
-- =============================================================
-- Objetivo: incluir categorias operacionais da campanha para uso
-- no cadastro de despesas e relatorios.
-- =============================================================

SET search_path TO sige, public;

INSERT INTO sige.categorias_permitidas_recurso (tipo_recurso, categoria, ativo)
SELECT tipos.tipo_recurso, categorias.categoria, TRUE
FROM (
    VALUES
        ('fundo_partidario'::sige.tipo_recurso_campanha_enum),
        ('fundo_eleitoral'::sige.tipo_recurso_campanha_enum),
        ('doacao_privada'::sige.tipo_recurso_campanha_enum)
) AS tipos(tipo_recurso)
CROSS JOIN (
    VALUES
        ('material_grafico'),
        ('producao_conteudo'),
        ('marketing_digital'),
        ('equipe_campanha'),
        ('transporte_logistica'),
        ('eventos_mobilizacao'),
        ('servicos_terceiros'),
        ('comunicacao_oficial'),
        ('custos_juridicos_contabeis')
) AS categorias(categoria)
ON CONFLICT (tipo_recurso, categoria)
DO UPDATE SET
    ativo = EXCLUDED.ativo,
    atualizado_em = NOW();
