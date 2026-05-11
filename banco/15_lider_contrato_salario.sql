-- =============================================================
-- SIGE - Script 15: Contrato e salario mensal do lider
-- =============================================================
-- Execucao: psql -h localhost -U postgres -d sige_db -f 15_lider_contrato_salario.sql
-- =============================================================

SET search_path TO sige, public;

ALTER TABLE sige.lideres
    ADD COLUMN IF NOT EXISTS salario_mensal NUMERIC(12, 2) DEFAULT NULL;

COMMENT ON COLUMN sige.lideres.salario_mensal
    IS 'Salario mensal definido no contrato do lider';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'ck_lideres_salario_mensal'
    ) THEN
        ALTER TABLE sige.lideres
            ADD CONSTRAINT ck_lideres_salario_mensal
            CHECK (salario_mensal IS NULL OR salario_mensal > 0);
    END IF;
END $$;

DROP VIEW IF EXISTS sige.vw_lideres_ativos CASCADE;

CREATE VIEW sige.vw_lideres_ativos
    WITH (security_barrier = true)
AS
SELECT
    l.id,
    l.nome,
    l.cpf_hash,
    l.telefone,
    l.bairro,
    l.votos_estimados,
    l.salario_mensal,
    l.observacoes,
    l.status,
    l.criado_em,
    l.atualizado_em,
    u.id   AS criado_por_id,
    u.nome AS criado_por_nome
FROM sige.lideres l
LEFT JOIN sige.usuarios u
    ON u.id = l.criado_por AND u.excluido_em IS NULL
WHERE l.excluido_em IS NULL;

COMMENT ON VIEW sige.vw_lideres_ativos
    IS 'Lideres ativos com usuario cadastrante e salario contratual';