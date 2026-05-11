-- =============================================================
-- SIGE - Script 12: Odometro, tipo de combustivel e alertas
-- =============================================================
-- Execucao: psql -h localhost -U postgres -d sige_db -f 12_combustivel_odometro_alertas.sql
-- =============================================================

SET search_path TO sige, public;

ALTER TABLE sige.combustivel_abastecimentos
    ADD COLUMN IF NOT EXISTS tipo_combustivel VARCHAR(20),
    ADD COLUMN IF NOT EXISTS odometro_atual INTEGER DEFAULT NULL;

UPDATE sige.combustivel_abastecimentos
SET tipo_combustivel = 'gasolina'
WHERE tipo_combustivel IS NULL;

ALTER TABLE sige.combustivel_abastecimentos
    ALTER COLUMN tipo_combustivel SET DEFAULT 'gasolina',
    ALTER COLUMN tipo_combustivel SET NOT NULL;

COMMENT ON COLUMN sige.combustivel_abastecimentos.tipo_combustivel
    IS 'Tipo de combustivel utilizado no abastecimento: gasolina ou diesel';

COMMENT ON COLUMN sige.combustivel_abastecimentos.odometro_atual
    IS 'Quilometragem atual do veiculo no momento do abastecimento';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'ck_combustivel_abastecimentos_tipo_combustivel'
    ) THEN
        ALTER TABLE sige.combustivel_abastecimentos
            ADD CONSTRAINT ck_combustivel_abastecimentos_tipo_combustivel
            CHECK (tipo_combustivel IN ('gasolina', 'diesel'));
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'ck_combustivel_abastecimentos_odometro_atual'
    ) THEN
        ALTER TABLE sige.combustivel_abastecimentos
            ADD CONSTRAINT ck_combustivel_abastecimentos_odometro_atual
            CHECK (odometro_atual IS NULL OR odometro_atual >= 0);
    END IF;
END $$;

DROP VIEW IF EXISTS sige.vw_combustivel_abastecimentos CASCADE;

CREATE VIEW sige.vw_combustivel_abastecimentos
    WITH (security_barrier = true)
AS
SELECT
    c.id,
    c.veiculo_descricao,
    c.placa_veiculo,
    c.tipo_combustivel,
    c.motorista_nome,
    c.local_abastecimento,
    c.odometro_atual,
    c.litros_abastecidos,
    c.valor_total,
    CASE
        WHEN COALESCE(c.litros_abastecidos, 0) > 0
            THEN ROUND((c.valor_total / c.litros_abastecidos)::NUMERIC, 2)::NUMERIC(12, 2)
        ELSE 0::NUMERIC(12, 2)
    END AS custo_por_litro,
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
    IS 'Abastecimentos ativos com lider vinculado, foto fiscal, tipo, odometro e custo por litro';

DROP VIEW IF EXISTS sige.vw_relatorio_combustivel_alertas CASCADE;

CREATE VIEW sige.vw_relatorio_combustivel_alertas AS
WITH base AS (
    SELECT
        c.id,
        c.placa_veiculo,
        c.veiculo_descricao,
        c.tipo_combustivel,
        c.lider_id,
        l.nome AS lider_nome,
        l.bairro AS lider_bairro,
        c.motorista_nome,
        c.local_abastecimento,
        c.odometro_atual,
        c.litros_abastecidos,
        c.valor_total,
        c.data_abastecimento,
        c.criado_em,
        ROUND((c.valor_total / NULLIF(c.litros_abastecidos, 0))::NUMERIC, 2)::NUMERIC(12, 2) AS custo_por_litro,
        LAG(c.odometro_atual) OVER (
            PARTITION BY c.placa_veiculo
            ORDER BY c.data_abastecimento, c.criado_em, c.id
        ) AS odometro_anterior,
        LAG(c.data_abastecimento) OVER (
            PARTITION BY c.placa_veiculo
            ORDER BY c.data_abastecimento, c.criado_em, c.id
        ) AS data_abastecimento_anterior,
        AVG(ROUND((c.valor_total / NULLIF(c.litros_abastecidos, 0))::NUMERIC, 4)) OVER (
            PARTITION BY c.tipo_combustivel
        )::NUMERIC(12, 4) AS media_custo_por_litro_tipo,
        ROW_NUMBER() OVER (
            PARTITION BY c.placa_veiculo
            ORDER BY c.data_abastecimento DESC, c.criado_em DESC, c.id DESC
        ) AS ordem_recente
    FROM sige.combustivel_abastecimentos c
    INNER JOIN sige.lideres l
        ON l.id = c.lider_id AND l.excluido_em IS NULL
    WHERE c.excluido_em IS NULL
), analise AS (
    SELECT
        b.*,
        CASE
            WHEN b.odometro_anterior IS NOT NULL
             AND b.odometro_atual IS NOT NULL
             AND b.odometro_atual > b.odometro_anterior
                THEN (b.odometro_atual - b.odometro_anterior)::NUMERIC(12, 2)
            ELSE NULL::NUMERIC(12, 2)
        END AS km_rodados,
        CASE
            WHEN b.odometro_anterior IS NOT NULL
             AND b.odometro_atual IS NOT NULL
             AND b.odometro_atual > b.odometro_anterior
             AND COALESCE(b.litros_abastecidos, 0) > 0
                THEN ROUND(((b.odometro_atual - b.odometro_anterior)::NUMERIC / b.litros_abastecidos)::NUMERIC, 2)::NUMERIC(12, 2)
            ELSE NULL::NUMERIC(12, 2)
        END AS consumo_km_l,
        CASE
            WHEN b.data_abastecimento_anterior IS NOT NULL
                THEN ROUND((EXTRACT(EPOCH FROM (b.data_abastecimento - b.data_abastecimento_anterior)) / 3600.0)::NUMERIC, 2)::NUMERIC(12, 2)
            ELSE NULL::NUMERIC(12, 2)
        END AS horas_desde_anterior
    FROM base b
), referencias AS (
    SELECT
        a.*,
        AVG(a.consumo_km_l) FILTER (WHERE a.consumo_km_l IS NOT NULL) OVER (
            PARTITION BY a.tipo_combustivel
        )::NUMERIC(12, 4) AS media_consumo_km_l_tipo
    FROM analise a
)
SELECT
    alertas.id,
    alertas.placa_veiculo,
    alertas.veiculo_descricao,
    alertas.tipo_combustivel,
    alertas.lider_id,
    alertas.lider_nome,
    alertas.lider_bairro,
    alertas.motorista_nome,
    alertas.local_abastecimento,
    alertas.data_abastecimento,
    alertas.odometro_atual,
    alertas.odometro_anterior,
    alertas.km_rodados,
    alertas.horas_desde_anterior,
    alertas.litros_abastecidos,
    alertas.valor_total,
    alertas.custo_por_litro,
    alertas.media_custo_por_litro_tipo,
    alertas.consumo_km_l,
    alertas.media_consumo_km_l_tipo,
    alertas.alerta_codigo,
    alertas.alerta_titulo,
    alertas.alerta_descricao,
    alertas.alerta_nivel,
    alertas.desvio_percentual
FROM (
    SELECT
        r.*,
        'preco_litro_fora_padrao'::VARCHAR(40) AS alerta_codigo,
        'Preco por litro acima da media'::VARCHAR(120) AS alerta_titulo,
        (
            'Este abastecimento ficou '
            || ROUND((((r.custo_por_litro / NULLIF(r.media_custo_por_litro_tipo, 0)) - 1) * 100)::NUMERIC, 1)::TEXT
            || '% acima da media do '
            || UPPER(r.tipo_combustivel)
            || '.'
        )::TEXT AS alerta_descricao,
        'medio'::VARCHAR(20) AS alerta_nivel,
        ROUND((((r.custo_por_litro / NULLIF(r.media_custo_por_litro_tipo, 0)) - 1) * 100)::NUMERIC, 2)::NUMERIC(12, 2) AS desvio_percentual
    FROM referencias r
    WHERE r.ordem_recente = 1
      AND r.media_custo_por_litro_tipo IS NOT NULL
      AND r.media_custo_por_litro_tipo > 0
            AND r.custo_por_litro > r.media_custo_por_litro_tipo * 1.12

    UNION ALL

    SELECT
        r.*,
        'consumo_acima_frota'::VARCHAR(40) AS alerta_codigo,
        'Veiculo consumindo acima do padrao'::VARCHAR(120) AS alerta_titulo,
        (
            'Esta placa esta gastando '
            || ROUND((((r.media_consumo_km_l_tipo / NULLIF(r.consumo_km_l, 0)) - 1) * 100)::NUMERIC, 1)::TEXT
            || '% a mais por km do que a media dos veiculos '
            || UPPER(r.tipo_combustivel)
            || '.'
        )::TEXT AS alerta_descricao,
        'alto'::VARCHAR(20) AS alerta_nivel,
        ROUND((((r.media_consumo_km_l_tipo / NULLIF(r.consumo_km_l, 0)) - 1) * 100)::NUMERIC, 2)::NUMERIC(12, 2) AS desvio_percentual
    FROM referencias r
    WHERE r.ordem_recente = 1
      AND r.consumo_km_l IS NOT NULL
      AND r.media_consumo_km_l_tipo IS NOT NULL
      AND r.media_consumo_km_l_tipo > 0
            AND r.consumo_km_l < r.media_consumo_km_l_tipo * 0.75

    UNION ALL

    SELECT
        r.*,
        'abastecimento_suspeito'::VARCHAR(40) AS alerta_codigo,
        'Abastecimento suspeito'::VARCHAR(120) AS alerta_titulo,
        (
            CASE
                WHEN r.odometro_anterior IS NOT NULL
                 AND r.odometro_atual IS NOT NULL
                 AND r.odometro_atual <= r.odometro_anterior
                    THEN 'O odometro atual ficou menor ou igual ao anterior para a mesma placa.'
                ELSE 'A placa recebeu novo abastecimento em intervalo curto e com baixa rodagem.'
            END
        )::TEXT AS alerta_descricao,
        'alto'::VARCHAR(20) AS alerta_nivel,
        NULL::NUMERIC(12, 2) AS desvio_percentual
    FROM referencias r
    WHERE r.ordem_recente = 1
      AND (
            (r.odometro_anterior IS NOT NULL AND r.odometro_atual IS NOT NULL AND r.odometro_atual <= r.odometro_anterior)
           OR (r.horas_desde_anterior IS NOT NULL AND r.km_rodados IS NOT NULL AND r.horas_desde_anterior <= 18 AND r.km_rodados < 120)
      )
) AS alertas;

COMMENT ON VIEW sige.vw_relatorio_combustivel_alertas
    IS 'Alertas por placa para preco fora do padrao, consumo acima da frota e abastecimento suspeito';

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
    NOW() AS gerado_em;

COMMENT ON VIEW sige.vw_relatorio_resumo
    IS 'Resumo geral do sistema com metricas ampliadas e total de alertas de combustivel';