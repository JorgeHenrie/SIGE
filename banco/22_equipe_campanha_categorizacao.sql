-- =============================================================
-- SIGE - Script 22: Categorizacao de membros da equipe de campanha
-- =============================================================
-- Execucao: psql -h localhost -U postgres -d sige_db -f 22_equipe_campanha_categorizacao.sql
-- =============================================================

SET search_path TO sige, public;

ALTER TABLE sige.lideres
    ADD COLUMN IF NOT EXISTS equipe_area VARCHAR(60) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS equipe_funcao VARCHAR(120) DEFAULT NULL;

COMMENT ON COLUMN sige.lideres.equipe_area
    IS 'Area macro da equipe de campanha: direcao_estrategia, financeiro_juridico, marketing_comunicacao, operacao_rua, logistica, agenda_apoio';

COMMENT ON COLUMN sige.lideres.equipe_funcao
    IS 'Funcao operacional do membro na equipe de campanha';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'ck_lideres_equipe_area'
    ) THEN
        ALTER TABLE sige.lideres
            ADD CONSTRAINT ck_lideres_equipe_area
            CHECK (
                equipe_area IS NULL OR equipe_area IN (
                    'direcao_estrategia',
                    'financeiro_juridico',
                    'marketing_comunicacao',
                    'operacao_rua',
                    'logistica',
                    'agenda_apoio'
                )
            );
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
    l.equipe_area,
    l.equipe_funcao,
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
    IS 'Lideres ativos com salario, area e funcao na equipe de campanha';
