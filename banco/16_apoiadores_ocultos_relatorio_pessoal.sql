-- =============================================================
-- SIGE - Script 16: Apoiadores ocultos e relatorios de pessoal
-- =============================================================
-- Execucao: psql -h localhost -U postgres -d sige_db -f 16_apoiadores_ocultos_relatorio_pessoal.sql
-- =============================================================

SET search_path TO sige, public;

ALTER TABLE sige.apoiadores
    ADD COLUMN IF NOT EXISTS ativo BOOLEAN DEFAULT FALSE;

UPDATE sige.apoiadores
SET ativo = FALSE
WHERE ativo IS DISTINCT FROM FALSE;

ALTER TABLE sige.apoiadores
    ALTER COLUMN ativo SET DEFAULT FALSE,
    ALTER COLUMN ativo SET NOT NULL;

COMMENT ON COLUMN sige.apoiadores.ativo
    IS 'Controla se o apoiador deve aparecer nas views operacionais. Temporariamente desativado por padrao.';

DROP VIEW IF EXISTS sige.vw_apoiadores_ativos CASCADE;

CREATE VIEW sige.vw_apoiadores_ativos
    WITH (security_barrier = true)
AS
SELECT
    a.id,
    a.nome,
    a.cpf_hash,
    a.telefone,
    a.bairro,
    a.status_politico,
    a.observacoes,
    a.criado_em,
    a.atualizado_em,
    l.id     AS lider_id,
    l.nome   AS lider_nome,
    l.bairro AS lider_bairro
FROM sige.apoiadores a
INNER JOIN sige.lideres l
    ON l.id = a.lider_id AND l.excluido_em IS NULL
WHERE a.excluido_em IS NULL
  AND a.ativo = TRUE;

COMMENT ON VIEW sige.vw_apoiadores_ativos
    IS 'Apoiadores ativos e visiveis com dados do lider vinculado';

DROP VIEW IF EXISTS sige.vw_relatorio_por_lider CASCADE;

CREATE VIEW sige.vw_relatorio_por_lider AS
WITH combustivel_mes AS (
    SELECT
        c.lider_id,
        COUNT(c.id) AS total_abastecimentos_combustivel,
        COALESCE(SUM(c.valor_total) FILTER (
            WHERE date_trunc('month', c.data_abastecimento) = date_trunc('month', NOW())
        ), 0)::NUMERIC(12, 2) AS total_combustivel_mes_atual
    FROM sige.combustivel_abastecimentos c
    WHERE c.excluido_em IS NULL
    GROUP BY c.lider_id
)
SELECT
    l.id AS lider_id,
    l.nome AS lider_nome,
    l.bairro AS lider_bairro,
    l.votos_estimados,
    COALESCE(l.salario_mensal, 0)::NUMERIC(12, 2) AS salario_mensal,
    COALESCE(cm.total_abastecimentos_combustivel, 0) AS total_abastecimentos_combustivel,
    COALESCE(cm.total_combustivel_mes_atual, 0)::NUMERIC(12, 2) AS total_combustivel_mes_atual,
    (COALESCE(l.salario_mensal, 0) + COALESCE(cm.total_combustivel_mes_atual, 0))::NUMERIC(12, 2) AS custo_mensal_total,
    0::BIGINT AS total_apoiadores,
    0::BIGINT AS qtd_apoiadores,
    0::BIGINT AS qtd_indecisos,
    0::BIGINT AS qtd_oposicao,
    0::NUMERIC(5, 1) AS perc_apoiadores,
    0::NUMERIC(5, 1) AS perc_indecisos,
    0::NUMERIC(5, 1) AS perc_oposicao,
    DENSE_RANK() OVER (ORDER BY l.votos_estimados DESC, l.nome ASC) AS ranking_votos,
    DENSE_RANK() OVER (ORDER BY COALESCE(l.salario_mensal, 0) DESC, l.nome ASC) AS ranking_apoiadores
FROM sige.lideres l
LEFT JOIN combustivel_mes cm
    ON cm.lider_id = l.id
WHERE l.excluido_em IS NULL;

COMMENT ON VIEW sige.vw_relatorio_por_lider
    IS 'Relatorio por lider com votos, salario contratual e gasto mensal de combustivel';

DROP VIEW IF EXISTS sige.vw_relatorio_por_bairro CASCADE;

CREATE VIEW sige.vw_relatorio_por_bairro AS
WITH combustivel_mes AS (
    SELECT
        c.lider_id,
        COALESCE(SUM(c.valor_total) FILTER (
            WHERE date_trunc('month', c.data_abastecimento) = date_trunc('month', NOW())
        ), 0)::NUMERIC(12, 2) AS total_combustivel_mes_atual
    FROM sige.combustivel_abastecimentos c
    WHERE c.excluido_em IS NULL
    GROUP BY c.lider_id
)
SELECT
    COALESCE(l.bairro, '(sem bairro)') AS bairro,
    'lider'::VARCHAR(20) AS tipo,
    COUNT(*) AS total,
    COALESCE(SUM(l.votos_estimados), 0) AS votos_estimados,
    0::BIGINT AS qtd_apoiadores,
    0::BIGINT AS qtd_indecisos,
    0::BIGINT AS qtd_oposicao,
    COALESCE(SUM(COALESCE(l.salario_mensal, 0)), 0)::NUMERIC(12, 2) AS total_folha_mensal,
    COALESCE(SUM(COALESCE(cm.total_combustivel_mes_atual, 0)), 0)::NUMERIC(12, 2) AS total_combustivel_mes_atual,
    COALESCE(SUM(COALESCE(l.salario_mensal, 0) + COALESCE(cm.total_combustivel_mes_atual, 0)), 0)::NUMERIC(12, 2) AS custo_mensal_total
FROM sige.lideres l
LEFT JOIN combustivel_mes cm
    ON cm.lider_id = l.id
WHERE l.excluido_em IS NULL
GROUP BY COALESCE(l.bairro, '(sem bairro)');

COMMENT ON VIEW sige.vw_relatorio_por_bairro
    IS 'Consolidacao por bairro com liderancas, votos e custos mensais de pessoal e combustivel';

DROP VIEW IF EXISTS sige.vw_relatorio_consolidado CASCADE;

CREATE VIEW sige.vw_relatorio_consolidado AS
WITH combustivel_mes AS (
    SELECT
        c.lider_id,
        COUNT(c.id) AS total_abastecimentos_combustivel,
        COALESCE(SUM(c.valor_total) FILTER (
            WHERE date_trunc('month', c.data_abastecimento) = date_trunc('month', NOW())
        ), 0)::NUMERIC(12, 2) AS total_combustivel_mes_atual,
        MAX(c.data_abastecimento) AS ultimo_abastecimento
    FROM sige.combustivel_abastecimentos c
    WHERE c.excluido_em IS NULL
    GROUP BY c.lider_id
)
SELECT
    l.id AS lider_id,
    l.nome AS lider_nome,
    l.bairro AS lider_bairro,
    l.votos_estimados,
    COALESCE(l.salario_mensal, 0)::NUMERIC(12, 2) AS salario_mensal,
    l.status AS lider_ativo,
    l.criado_em AS lider_cadastrado_em,
    0::BIGINT AS total_vinculados,
    0::BIGINT AS apoiadores,
    0::BIGINT AS indecisos,
    0::BIGINT AS oposicao,
    l.votos_estimados AS potencial_total_votos,
    COALESCE(cm.total_abastecimentos_combustivel, 0) AS total_abastecimentos_combustivel,
    COALESCE(cm.total_combustivel_mes_atual, 0)::NUMERIC(12, 2) AS total_combustivel_mes_atual,
    (COALESCE(l.salario_mensal, 0) + COALESCE(cm.total_combustivel_mes_atual, 0))::NUMERIC(12, 2) AS custo_mensal_total,
    cm.ultimo_abastecimento,
    DENSE_RANK() OVER (ORDER BY l.votos_estimados DESC, l.nome ASC) AS posicao_ranking
FROM sige.lideres l
LEFT JOIN combustivel_mes cm
    ON cm.lider_id = l.id
WHERE l.excluido_em IS NULL;

COMMENT ON VIEW sige.vw_relatorio_consolidado
    IS 'Relatorio consolidado por lider com votos, salario e custo mensal operacional';

DROP VIEW IF EXISTS sige.vw_relatorio_resumo CASCADE;

CREATE VIEW sige.vw_relatorio_resumo AS
SELECT
    (SELECT COUNT(*) FROM sige.lideres WHERE excluido_em IS NULL) AS total_lideres,
    0::BIGINT AS total_apoiadores,
    (SELECT COALESCE(SUM(votos_estimados), 0) FROM sige.lideres WHERE excluido_em IS NULL) AS total_votos_estimados,
    (SELECT COALESCE(ROUND(AVG(votos_estimados), 2), 0) FROM sige.lideres WHERE excluido_em IS NULL) AS media_votos_por_lider,
    0::BIGINT AS total_apoiadores_confirmados,
    0::BIGINT AS total_indecisos,
    0::BIGINT AS total_oposicao,
    (SELECT COALESCE(SUM(COALESCE(salario_mensal, 0)), 0)::NUMERIC(12, 2) FROM sige.lideres WHERE excluido_em IS NULL) AS total_folha_lideres_mensal,
    (
        SELECT COALESCE(ROUND(AVG(COALESCE(salario_mensal, 0)), 2), 0)::NUMERIC(12, 2)
        FROM sige.lideres
        WHERE excluido_em IS NULL
    ) AS media_salario_lider,
    (SELECT COUNT(*) FROM sige.combustivel_abastecimentos WHERE excluido_em IS NULL) AS total_abastecimentos_combustivel,
    (SELECT COUNT(*) FROM sige.vw_relatorio_combustivel_alertas) AS total_alertas_combustivel,
    (SELECT COALESCE(SUM(litros_abastecidos), 0)::NUMERIC(12, 2) FROM sige.combustivel_abastecimentos WHERE excluido_em IS NULL) AS total_litros_combustivel,
    (SELECT COALESCE(SUM(valor_total), 0)::NUMERIC(12, 2) FROM sige.combustivel_abastecimentos WHERE excluido_em IS NULL) AS total_gasto_combustivel,
    (
        SELECT CASE
            WHEN COALESCE(SUM(litros_abastecidos), 0) > 0
                THEN ROUND((COALESCE(SUM(valor_total), 0) / SUM(litros_abastecidos))::NUMERIC, 2)::NUMERIC(12, 2)
            ELSE 0::NUMERIC(12, 2)
        END
        FROM sige.combustivel_abastecimentos
        WHERE excluido_em IS NULL
    ) AS custo_medio_litro_combustivel,
    (
        SELECT COALESCE(SUM(litros_abastecidos), 0)::NUMERIC(12, 2)
        FROM sige.combustivel_abastecimentos
        WHERE excluido_em IS NULL
          AND date_trunc('week', data_abastecimento) = date_trunc('week', NOW())
    ) AS total_litros_combustivel_semana_atual,
    (
        SELECT COALESCE(SUM(litros_abastecidos), 0)::NUMERIC(12, 2)
        FROM sige.combustivel_abastecimentos
        WHERE excluido_em IS NULL
          AND date_trunc('month', data_abastecimento) = date_trunc('month', NOW())
    ) AS total_litros_combustivel_mes_atual,
    (
        SELECT COALESCE(SUM(valor_total), 0)::NUMERIC(12, 2)
        FROM sige.combustivel_abastecimentos
        WHERE excluido_em IS NULL
          AND date_trunc('week', data_abastecimento) = date_trunc('week', NOW())
    ) AS total_gasto_combustivel_semana_atual,
    (
        SELECT COALESCE(SUM(valor_total), 0)::NUMERIC(12, 2)
        FROM sige.combustivel_abastecimentos
        WHERE excluido_em IS NULL
          AND date_trunc('month', data_abastecimento) = date_trunc('month', NOW())
    ) AS total_gasto_combustivel_mes_atual,
    (
        (SELECT COALESCE(SUM(COALESCE(salario_mensal, 0)), 0)::NUMERIC(12, 2) FROM sige.lideres WHERE excluido_em IS NULL)
        +
        (
            SELECT COALESCE(SUM(valor_total), 0)::NUMERIC(12, 2)
            FROM sige.combustivel_abastecimentos
            WHERE excluido_em IS NULL
              AND date_trunc('month', data_abastecimento) = date_trunc('month', NOW())
        )
    )::NUMERIC(12, 2) AS total_custo_operacional_mes_atual,
    NOW() AS gerado_em;

COMMENT ON VIEW sige.vw_relatorio_resumo
    IS 'Resumo geral com liderancas, folha mensal e custos operacionais do mes';