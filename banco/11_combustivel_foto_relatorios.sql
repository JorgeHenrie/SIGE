-- =============================================================
-- SIGE - Script 11: Foto da nota fiscal e metricas avancadas
-- =============================================================
-- Execucao: psql -h localhost -U postgres -d sige_db -f 11_combustivel_foto_relatorios.sql
-- =============================================================

SET search_path TO sige, public;

ALTER TABLE sige.combustivel_abastecimentos
    ADD COLUMN IF NOT EXISTS foto_nota_fiscal_caminho VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS foto_nota_fiscal_nome VARCHAR(160) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS foto_nota_fiscal_mime VARCHAR(80) DEFAULT NULL;

COMMENT ON COLUMN sige.combustivel_abastecimentos.foto_nota_fiscal_caminho
    IS 'Caminho publico relativo da foto da nota fiscal salva no servidor';

COMMENT ON COLUMN sige.combustivel_abastecimentos.foto_nota_fiscal_nome
    IS 'Nome original do arquivo enviado para a foto da nota fiscal';

COMMENT ON COLUMN sige.combustivel_abastecimentos.foto_nota_fiscal_mime
    IS 'Mime type validado da foto da nota fiscal';

DROP VIEW IF EXISTS sige.vw_combustivel_abastecimentos CASCADE;

CREATE VIEW sige.vw_combustivel_abastecimentos
    WITH (security_barrier = true)
AS
SELECT
    c.id,
    c.veiculo_descricao,
    c.placa_veiculo,
    c.motorista_nome,
    c.local_abastecimento,
    c.litros_abastecidos,
    c.valor_total,
    c.finalidade,
    c.numero_nota_fiscal,
    c.foto_nota_fiscal_caminho,
    c.foto_nota_fiscal_nome,
    c.foto_nota_fiscal_mime,
    c.data_abastecimento,
    c.observacoes,
    c.criado_por_usuario_id,
    c.criado_em,
    c.atualizado_em,
    l.id AS lider_id,
    l.nome AS lider_nome,
    l.bairro AS lider_bairro,
    u.nome AS criado_por_usuario_nome
FROM sige.combustivel_abastecimentos c
INNER JOIN sige.lideres l
    ON l.id = c.lider_id AND l.excluido_em IS NULL
LEFT JOIN sige.usuarios u
    ON u.id = c.criado_por_usuario_id AND u.excluido_em IS NULL
WHERE c.excluido_em IS NULL;

COMMENT ON VIEW sige.vw_combustivel_abastecimentos
    IS 'Abastecimentos ativos com lider vinculado, dados reais da nota, foto fiscal e usuario responsavel';

DROP VIEW IF EXISTS sige.vw_relatorio_combustivel_por_lider CASCADE;

CREATE VIEW sige.vw_relatorio_combustivel_por_lider AS
SELECT
    l.id AS lider_id,
    l.nome AS lider_nome,
    l.bairro AS lider_bairro,
    COUNT(c.id) AS total_abastecimentos,
    COALESCE(SUM(c.litros_abastecidos), 0)::NUMERIC(12, 2) AS total_litros,
    COALESCE(SUM(c.valor_total), 0)::NUMERIC(12, 2) AS total_gasto,
    CASE
        WHEN COALESCE(SUM(c.litros_abastecidos), 0) > 0
            THEN ROUND((COALESCE(SUM(c.valor_total), 0) / SUM(c.litros_abastecidos))::NUMERIC, 2)::NUMERIC(12, 2)
        ELSE 0::NUMERIC(12, 2)
    END AS custo_medio_litro,
    COALESCE(SUM(c.litros_abastecidos) FILTER (
        WHERE date_trunc('week', c.data_abastecimento) = date_trunc('week', NOW())
    ), 0)::NUMERIC(12, 2) AS litros_semana_atual,
    COALESCE(SUM(c.valor_total) FILTER (
        WHERE date_trunc('week', c.data_abastecimento) = date_trunc('week', NOW())
    ), 0)::NUMERIC(12, 2) AS total_semana_atual,
    COALESCE(SUM(c.litros_abastecidos) FILTER (
        WHERE date_trunc('month', c.data_abastecimento) = date_trunc('month', NOW())
    ), 0)::NUMERIC(12, 2) AS litros_mes_atual,
    COALESCE(SUM(c.valor_total) FILTER (
        WHERE date_trunc('month', c.data_abastecimento) = date_trunc('month', NOW())
    ), 0)::NUMERIC(12, 2) AS total_mes_atual,
    MAX(c.data_abastecimento) AS ultimo_abastecimento
FROM sige.lideres l
LEFT JOIN sige.combustivel_abastecimentos c
    ON c.lider_id = l.id AND c.excluido_em IS NULL
WHERE l.excluido_em IS NULL
GROUP BY l.id, l.nome, l.bairro;

COMMENT ON VIEW sige.vw_relatorio_combustivel_por_lider
    IS 'Consolidacao de combustivel por lider com gasto, litros e custo medio por litro';

DROP VIEW IF EXISTS sige.vw_relatorio_combustivel_semanal CASCADE;

CREATE VIEW sige.vw_relatorio_combustivel_semanal AS
SELECT
    date_trunc('week', c.data_abastecimento)::DATE AS semana_referencia,
    COUNT(c.id) AS total_abastecimentos,
    COUNT(DISTINCT c.lider_id) AS total_lideres,
    COALESCE(SUM(c.litros_abastecidos), 0)::NUMERIC(12, 2) AS total_litros,
    COALESCE(SUM(c.valor_total), 0)::NUMERIC(12, 2) AS total_gasto,
    CASE
        WHEN COALESCE(SUM(c.litros_abastecidos), 0) > 0
            THEN ROUND((COALESCE(SUM(c.valor_total), 0) / SUM(c.litros_abastecidos))::NUMERIC, 2)::NUMERIC(12, 2)
        ELSE 0::NUMERIC(12, 2)
    END AS custo_medio_litro
FROM sige.combustivel_abastecimentos c
WHERE c.excluido_em IS NULL
GROUP BY date_trunc('week', c.data_abastecimento)
ORDER BY semana_referencia DESC;

COMMENT ON VIEW sige.vw_relatorio_combustivel_semanal
    IS 'Consolidacao semanal dos abastecimentos com gasto, litros e custo medio por litro';

DROP VIEW IF EXISTS sige.vw_relatorio_combustivel_mensal CASCADE;

CREATE VIEW sige.vw_relatorio_combustivel_mensal AS
SELECT
    date_trunc('month', c.data_abastecimento)::DATE AS mes_referencia,
    COUNT(c.id) AS total_abastecimentos,
    COUNT(DISTINCT c.lider_id) AS total_lideres,
    COALESCE(SUM(c.litros_abastecidos), 0)::NUMERIC(12, 2) AS total_litros,
    COALESCE(SUM(c.valor_total), 0)::NUMERIC(12, 2) AS total_gasto,
    CASE
        WHEN COALESCE(SUM(c.litros_abastecidos), 0) > 0
            THEN ROUND((COALESCE(SUM(c.valor_total), 0) / SUM(c.litros_abastecidos))::NUMERIC, 2)::NUMERIC(12, 2)
        ELSE 0::NUMERIC(12, 2)
    END AS custo_medio_litro
FROM sige.combustivel_abastecimentos c
WHERE c.excluido_em IS NULL
GROUP BY date_trunc('month', c.data_abastecimento)
ORDER BY mes_referencia DESC;

COMMENT ON VIEW sige.vw_relatorio_combustivel_mensal
    IS 'Consolidacao mensal dos abastecimentos com gasto, litros e custo medio por litro';

DROP VIEW IF EXISTS sige.vw_relatorio_resumo CASCADE;

CREATE VIEW sige.vw_relatorio_resumo AS
SELECT
    (SELECT COUNT(*) FROM sige.lideres WHERE excluido_em IS NULL) AS total_lideres,
    (SELECT COUNT(*) FROM sige.apoiadores WHERE excluido_em IS NULL) AS total_apoiadores,
    (SELECT COALESCE(SUM(votos_estimados), 0) FROM sige.lideres WHERE excluido_em IS NULL) AS total_votos_estimados,
    (SELECT COALESCE(ROUND(AVG(votos_estimados), 2), 0) FROM sige.lideres WHERE excluido_em IS NULL) AS media_votos_por_lider,
    (SELECT COUNT(*) FROM sige.apoiadores WHERE excluido_em IS NULL AND status_politico = 'apoiador') AS total_apoiadores_confirmados,
    (SELECT COUNT(*) FROM sige.apoiadores WHERE excluido_em IS NULL AND status_politico = 'indeciso') AS total_indecisos,
    (SELECT COUNT(*) FROM sige.apoiadores WHERE excluido_em IS NULL AND status_politico = 'oposicao') AS total_oposicao,
    (SELECT COUNT(*) FROM sige.combustivel_abastecimentos WHERE excluido_em IS NULL) AS total_abastecimentos_combustivel,
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
    NOW() AS gerado_em;

COMMENT ON VIEW sige.vw_relatorio_resumo
    IS 'Resumo geral do sistema com totais politicos e metricas ampliadas de combustivel';