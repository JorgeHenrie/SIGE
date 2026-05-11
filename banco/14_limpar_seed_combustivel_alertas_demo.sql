-- =============================================================
-- SIGE - Script 14: Limpeza da seed demo de alertas de combustivel
-- =============================================================
-- Execucao: psql -h localhost -U postgres -d sige_db -f 14_limpar_seed_combustivel_alertas_demo.sql
-- Objetivo: remover os registros criados pelo script demo
--            13_seed_combustivel_alertas_demo.sql.
-- =============================================================

SET search_path TO sige, public;

DELETE FROM sige.combustivel_abastecimentos
WHERE observacoes = 'SEED DEMO ALERTAS COMBUSTIVEL'
   OR placa_veiculo IN (
        'DEMG001',
        'DEMG002',
        'DEMG099',
        'DEMD001',
        'DEMD002',
        'DEMD099'
   );

SELECT COUNT(*) AS registros_demo_restantes
FROM sige.combustivel_abastecimentos
WHERE observacoes = 'SEED DEMO ALERTAS COMBUSTIVEL'
   OR placa_veiculo IN (
        'DEMG001',
        'DEMG002',
        'DEMG099',
        'DEMD001',
        'DEMD002',
        'DEMD099'
   );