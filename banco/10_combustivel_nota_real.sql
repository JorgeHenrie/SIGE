-- =============================================================
-- SIGE - Script 10: Campos da nota real de combustivel
-- =============================================================
-- Execucao: psql -h localhost -U postgres -d sige_db -f 10_combustivel_nota_real.sql
-- =============================================================

SET search_path TO sige, public;

ALTER TABLE sige.combustivel_abastecimentos
    ADD COLUMN IF NOT EXISTS veiculo_descricao VARCHAR(120) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS motorista_nome VARCHAR(120) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS local_abastecimento VARCHAR(160) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS litros_abastecidos NUMERIC(10, 2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS finalidade TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS numero_nota_fiscal VARCHAR(40) DEFAULT NULL;

COMMENT ON COLUMN sige.combustivel_abastecimentos.veiculo_descricao
    IS 'Identificacao amigavel do veiculo na operacao, ex.: Carro 1';

COMMENT ON COLUMN sige.combustivel_abastecimentos.motorista_nome
    IS 'Nome do motorista responsavel pelo abastecimento';

COMMENT ON COLUMN sige.combustivel_abastecimentos.local_abastecimento
    IS 'Posto, cidade ou referencia do local do abastecimento';

COMMENT ON COLUMN sige.combustivel_abastecimentos.litros_abastecidos
    IS 'Quantidade de litros abastecidos no lancamento';

COMMENT ON COLUMN sige.combustivel_abastecimentos.finalidade
    IS 'Motivo operacional do abastecimento, ex.: visita a bairros e reuniao';

COMMENT ON COLUMN sige.combustivel_abastecimentos.numero_nota_fiscal
    IS 'Numero da nota fiscal ou cupom do abastecimento';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'ck_combustivel_abastecimentos_litros_abastecidos'
    ) THEN
        ALTER TABLE sige.combustivel_abastecimentos
            ADD CONSTRAINT ck_combustivel_abastecimentos_litros_abastecidos
            CHECK (litros_abastecidos IS NULL OR litros_abastecidos > 0);
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
    c.motorista_nome,
    c.local_abastecimento,
    c.litros_abastecidos,
    c.valor_total,
    c.finalidade,
    c.numero_nota_fiscal,
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
    IS 'Abastecimentos ativos com lider vinculado, dados reais da nota e usuario responsavel pelo lancamento';