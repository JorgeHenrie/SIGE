-- =============================================================
-- SIGE - Script 09: Módulo de combustível
-- =============================================================
-- Execução: psql -h localhost -U postgres -d sige_db -f 09_combustivel.sql
-- =============================================================

SET search_path TO sige, public;

-- -------------------------------------------------------------
-- TABELA: combustivel_abastecimentos
-- Controla lançamentos de combustível vinculados ao líder.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sige.combustivel_abastecimentos (
    id                    UUID                         NOT NULL DEFAULT gen_random_uuid(),
    lider_id              UUID                         NOT NULL,
    criado_por_usuario_id UUID                                  DEFAULT NULL,
    placa_veiculo         VARCHAR(10)                  NOT NULL,
    valor_total           NUMERIC(12, 2)               NOT NULL,
    data_abastecimento    TIMESTAMP WITHOUT TIME ZONE  NOT NULL DEFAULT NOW(),
    observacoes           TEXT                                  DEFAULT NULL,
    criado_em             TIMESTAMP WITHOUT TIME ZONE  NOT NULL DEFAULT NOW(),
    atualizado_em         TIMESTAMP WITHOUT TIME ZONE  NOT NULL DEFAULT NOW(),
    excluido_em           TIMESTAMP WITHOUT TIME ZONE           DEFAULT NULL,

    CONSTRAINT pk_combustivel_abastecimentos PRIMARY KEY (id),
    CONSTRAINT fk_combustivel_abastecimentos_lider FOREIGN KEY (lider_id)
        REFERENCES sige.lideres(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_combustivel_abastecimentos_criado_por FOREIGN KEY (criado_por_usuario_id)
        REFERENCES sige.usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT ck_combustivel_abastecimentos_valor_total CHECK (valor_total > 0)
);

COMMENT ON TABLE sige.combustivel_abastecimentos
    IS 'Lançamentos de combustível vinculados a líderes e seus veículos';

COMMENT ON COLUMN sige.combustivel_abastecimentos.placa_veiculo
    IS 'Placa do veículo abastecido, usada para rastreio financeiro';

COMMENT ON COLUMN sige.combustivel_abastecimentos.valor_total
    IS 'Valor total gasto no abastecimento, em moeda corrente';

CREATE INDEX IF NOT EXISTS ix_combustivel_abastecimentos_lider_id
    ON sige.combustivel_abastecimentos(lider_id)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_combustivel_abastecimentos_data_abastecimento
    ON sige.combustivel_abastecimentos(data_abastecimento DESC)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_combustivel_abastecimentos_placa
    ON sige.combustivel_abastecimentos(placa_veiculo)
    WHERE excluido_em IS NULL;

DROP VIEW IF EXISTS sige.vw_combustivel_abastecimentos CASCADE;

CREATE VIEW sige.vw_combustivel_abastecimentos
    WITH (security_barrier = true)
AS
SELECT
    c.id,
    c.placa_veiculo,
    c.valor_total,
    c.data_abastecimento,
    c.observacoes,
    c.criado_por_usuario_id,
    c.criado_em,
    c.atualizado_em,
    l.id   AS lider_id,
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
    IS 'Abastecimentos ativos com líder vinculado e usuário responsável pelo lançamento';

-- -------------------------------------------------------------
-- Views de relatório do módulo de combustível
-- -------------------------------------------------------------
DROP VIEW IF EXISTS sige.vw_relatorio_combustivel_por_lider CASCADE;

CREATE VIEW sige.vw_relatorio_combustivel_por_lider AS
SELECT
    l.id AS lider_id,
    l.nome AS lider_nome,
    l.bairro AS lider_bairro,
    COUNT(c.id) AS total_abastecimentos,
    COALESCE(SUM(c.valor_total), 0)::NUMERIC(12, 2) AS total_gasto,
    COALESCE(SUM(c.valor_total) FILTER (
        WHERE date_trunc('week', c.data_abastecimento) = date_trunc('week', NOW())
    ), 0)::NUMERIC(12, 2) AS total_semana_atual,
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
    IS 'Consolidação de combustível por líder com totais geral, semanal e mensal';

DROP VIEW IF EXISTS sige.vw_relatorio_combustivel_semanal CASCADE;

CREATE VIEW sige.vw_relatorio_combustivel_semanal AS
SELECT
    date_trunc('week', c.data_abastecimento)::DATE AS semana_referencia,
    COUNT(c.id) AS total_abastecimentos,
    COUNT(DISTINCT c.lider_id) AS total_lideres,
    COALESCE(SUM(c.valor_total), 0)::NUMERIC(12, 2) AS total_gasto
FROM sige.combustivel_abastecimentos c
WHERE c.excluido_em IS NULL
GROUP BY date_trunc('week', c.data_abastecimento)
ORDER BY semana_referencia DESC;

COMMENT ON VIEW sige.vw_relatorio_combustivel_semanal
    IS 'Consolidação semanal dos abastecimentos lançados no sistema';

DROP VIEW IF EXISTS sige.vw_relatorio_combustivel_mensal CASCADE;

CREATE VIEW sige.vw_relatorio_combustivel_mensal AS
SELECT
    date_trunc('month', c.data_abastecimento)::DATE AS mes_referencia,
    COUNT(c.id) AS total_abastecimentos,
    COUNT(DISTINCT c.lider_id) AS total_lideres,
    COALESCE(SUM(c.valor_total), 0)::NUMERIC(12, 2) AS total_gasto
FROM sige.combustivel_abastecimentos c
WHERE c.excluido_em IS NULL
GROUP BY date_trunc('month', c.data_abastecimento)
ORDER BY mes_referencia DESC;

COMMENT ON VIEW sige.vw_relatorio_combustivel_mensal
    IS 'Consolidação mensal dos abastecimentos lançados no sistema';

-- -------------------------------------------------------------
-- Amplia o resumo geral com dados de combustível.
-- -------------------------------------------------------------
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
    (SELECT COALESCE(SUM(valor_total), 0)::NUMERIC(12, 2) FROM sige.combustivel_abastecimentos WHERE excluido_em IS NULL) AS total_gasto_combustivel,
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
    IS 'Resumo geral do sistema com totais políticos e indicadores de combustível';

DROP TRIGGER IF EXISTS trg_combustivel_abastecimentos_atualizado_em ON sige.combustivel_abastecimentos;

CREATE TRIGGER trg_combustivel_abastecimentos_atualizado_em
    BEFORE UPDATE ON sige.combustivel_abastecimentos
    FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();