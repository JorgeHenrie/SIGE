-- =============================================================
-- SIGE - Script 13: Seed opcional de alertas de combustivel
-- =============================================================
-- Execucao: psql -h localhost -U postgres -d sige_db -f 13_seed_combustivel_alertas_demo.sql
-- Objetivo: popular cenarios de demonstracao para a secao
--            "Alertas de combustivel" sem cadastro manual.
-- =============================================================

SET search_path TO sige, public;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM sige.lideres
        WHERE excluido_em IS NULL
    ) THEN
        RAISE EXCEPTION 'Nao existe lider ativo para vincular a seed demo de combustivel.';
    END IF;
END $$;

DELETE FROM sige.combustivel_abastecimentos
WHERE placa_veiculo IN (
    'DEMG001',
    'DEMG002',
    'DEMG099',
    'DEMD001',
    'DEMD002',
    'DEMD099'
);

WITH lider_base AS (
    SELECT id AS lider_id
    FROM sige.lideres
    WHERE excluido_em IS NULL
    ORDER BY nome ASC, id ASC
    LIMIT 1
), demo_abastecimentos (
    placa_veiculo,
    tipo_combustivel,
    veiculo_descricao,
    motorista_nome,
    local_abastecimento,
    odometro_atual,
    litros_abastecidos,
    valor_total,
    finalidade,
    numero_nota_fiscal,
    data_abastecimento,
    observacoes
) AS (
    VALUES
        (
            'DEMG001',
            'gasolina',
            'FIAT MOBI DEMO 01',
            'Equipe Demo',
            'POSTO MODELO CENTRO',
            10000,
            40.00,
            240.00,
            'Seed demo: linha base de gasolina para comparativo.',
            'DEMO-GAS-001-A',
            NOW() - INTERVAL '3 days 8 hours',
            'SEED DEMO ALERTAS COMBUSTIVEL'
        ),
        (
            'DEMG001',
            'gasolina',
            'FIAT MOBI DEMO 01',
            'Equipe Demo',
            'POSTO MODELO CENTRO',
            10480,
            40.00,
            244.00,
            'Seed demo: segunda linha base de gasolina.',
            'DEMO-GAS-001-B',
            NOW() - INTERVAL '2 days 2 hours',
            'SEED DEMO ALERTAS COMBUSTIVEL'
        ),
        (
            'DEMG002',
            'gasolina',
            'RENAULT KWID DEMO 02',
            'Equipe Demo',
            'POSTO MODELO ALDEOTA',
            20000,
            35.00,
            210.00,
            'Seed demo: linha base alternativa de gasolina.',
            'DEMO-GAS-002-A',
            NOW() - INTERVAL '3 days 3 hours',
            'SEED DEMO ALERTAS COMBUSTIVEL'
        ),
        (
            'DEMG002',
            'gasolina',
            'RENAULT KWID DEMO 02',
            'Equipe Demo',
            'POSTO MODELO ALDEOTA',
            20420,
            35.00,
            213.50,
            'Seed demo: segunda linha base alternativa de gasolina.',
            'DEMO-GAS-002-B',
            NOW() - INTERVAL '1 day 18 hours',
            'SEED DEMO ALERTAS COMBUSTIVEL'
        ),
        (
            'DEMG099',
            'gasolina',
            'CHEVROLET ONIX DEMO ALERTA',
            'Equipe Demo',
            'POSTO MODELO PRAIA',
            30000,
            30.00,
            180.00,
            'Seed demo: historico normal antes da anomalia.',
            'DEMO-GAS-099-A',
            NOW() - INTERVAL '14 hours',
            'SEED DEMO ALERTAS COMBUSTIVEL'
        ),
        (
            'DEMG099',
            'gasolina',
            'CHEVROLET ONIX DEMO ALERTA',
            'Equipe Demo',
            'POSTO MODELO PRAIA',
            30060,
            18.00,
            162.00,
            'Seed demo: anomalia completa com preco alto, baixa autonomia e reabastecimento suspeito.',
            'DEMO-GAS-099-B',
            NOW() - INTERVAL '4 hours',
            'SEED DEMO ALERTAS COMBUSTIVEL'
        ),
        (
            'DEMD001',
            'diesel',
            'SPRINTER DEMO 01',
            'Equipe Demo',
            'POSTO MODELO BR 116',
            50000,
            50.00,
            280.00,
            'Seed demo: linha base de diesel para comparativo.',
            'DEMO-DIE-001-A',
            NOW() - INTERVAL '4 days 9 hours',
            'SEED DEMO ALERTAS COMBUSTIVEL'
        ),
        (
            'DEMD001',
            'diesel',
            'SPRINTER DEMO 01',
            'Equipe Demo',
            'POSTO MODELO BR 116',
            50400,
            50.00,
            285.00,
            'Seed demo: segunda linha base de diesel.',
            'DEMO-DIE-001-B',
            NOW() - INTERVAL '3 days 4 hours',
            'SEED DEMO ALERTAS COMBUSTIVEL'
        ),
        (
            'DEMD002',
            'diesel',
            'HILUX DEMO 02',
            'Equipe Demo',
            'POSTO MODELO ANEL VIARIO',
            60000,
            48.00,
            268.80,
            'Seed demo: linha base alternativa de diesel.',
            'DEMO-DIE-002-A',
            NOW() - INTERVAL '3 days 1 hour',
            'SEED DEMO ALERTAS COMBUSTIVEL'
        ),
        (
            'DEMD002',
            'diesel',
            'HILUX DEMO 02',
            'Equipe Demo',
            'POSTO MODELO ANEL VIARIO',
            60384,
            48.00,
            273.60,
            'Seed demo: segunda linha base alternativa de diesel.',
            'DEMO-DIE-002-B',
            NOW() - INTERVAL '1 day 16 hours',
            'SEED DEMO ALERTAS COMBUSTIVEL'
        ),
        (
            'DEMD099',
            'diesel',
            'IVECO DEMO ALERTA',
            'Equipe Demo',
            'POSTO MODELO CEASA',
            70000,
            50.00,
            280.00,
            'Seed demo: historico normal de diesel antes do alerta.',
            'DEMO-DIE-099-A',
            NOW() - INTERVAL '1 day 20 hours',
            'SEED DEMO ALERTAS COMBUSTIVEL'
        ),
        (
            'DEMD099',
            'diesel',
            'IVECO DEMO ALERTA',
            'Equipe Demo',
            'POSTO MODELO CEASA',
            70400,
            50.00,
            355.00,
            'Seed demo: preco acima do padrao sem suspeita de reabastecimento.',
            'DEMO-DIE-099-B',
            NOW() - INTERVAL '4 hours',
            'SEED DEMO ALERTAS COMBUSTIVEL'
        )
)
INSERT INTO sige.combustivel_abastecimentos (
    lider_id,
    criado_por_usuario_id,
    placa_veiculo,
    tipo_combustivel,
    veiculo_descricao,
    motorista_nome,
    local_abastecimento,
    odometro_atual,
    litros_abastecidos,
    valor_total,
    finalidade,
    numero_nota_fiscal,
    data_abastecimento,
    observacoes
)
SELECT
    lb.lider_id,
    NULL,
    d.placa_veiculo,
    d.tipo_combustivel,
    d.veiculo_descricao,
    d.motorista_nome,
    d.local_abastecimento,
    d.odometro_atual,
    d.litros_abastecidos,
    d.valor_total,
    d.finalidade,
    d.numero_nota_fiscal,
    d.data_abastecimento,
    d.observacoes
FROM demo_abastecimentos d
CROSS JOIN lider_base lb;

SELECT
    alerta_nivel,
    alerta_codigo,
    placa_veiculo,
    tipo_combustivel,
    alerta_titulo,
    custo_por_litro,
    consumo_km_l,
    horas_desde_anterior,
    km_rodados
FROM sige.vw_relatorio_combustivel_alertas
WHERE placa_veiculo IN ('DEMG099', 'DEMD099')
ORDER BY
    CASE alerta_nivel
        WHEN 'alto' THEN 1
        WHEN 'medio' THEN 2
        ELSE 3
    END,
    placa_veiculo ASC,
    alerta_codigo ASC;