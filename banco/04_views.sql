-- =============================================================
-- SIGE - Sistema de Mapeamento Político
-- Script 04: Views de leitura e relatórios
-- =============================================================
-- Execução: psql -h localhost -U postgres -d sige_db -f 04_views.sql
--
-- Estratégia:
--   Tabelas  → escrita (INSERT / UPDATE / DELETE via CRUD)
--   Views    → leitura (listagens, joins, relatórios, dashboard)
--
-- Todas as views de dados aplicam WHERE excluido_em IS NULL
-- automaticamente, evitando vazamento de registros deletados
-- independentemente do que a aplicação enviar.
-- =============================================================

SET search_path TO sige, public;

-- =============================================================
-- VIEWS OPERACIONAIS (listagens e detalhe)
-- =============================================================

-- -------------------------------------------------------------
-- vw_usuarios_ativos
-- Listagem segura de usuários para uso interno da aplicação.
-- Exclui a coluna senha — senha NUNCA trafega em listagens.
-- security_barrier: impede que predicados externos avaliem
-- linhas filtradas pelo soft delete antes do WHERE interno.
-- -------------------------------------------------------------
CREATE OR REPLACE VIEW sige.vw_usuarios_ativos
    WITH (security_barrier = true)
AS
SELECT
    id,
    nome,
    email,
    perfil,
    status,
    criado_em,
    atualizado_em
FROM sige.usuarios
WHERE excluido_em IS NULL;

COMMENT ON VIEW sige.vw_usuarios_ativos
    IS 'Usuários ativos — sem coluna senha, aplica soft delete automaticamente';

-- -------------------------------------------------------------
-- vw_lideres_ativos
-- Listagem de líderes com nome do usuário que cadastrou.
-- -------------------------------------------------------------
CREATE OR REPLACE VIEW sige.vw_lideres_ativos
    WITH (security_barrier = true)
AS
SELECT
    l.id,
    l.nome,
    l.cpf_hash,
    l.telefone,
    l.bairro,
    l.votos_estimados,
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
    IS 'Líderes ativos com nome do usuário cadastrante — soft delete aplicado';

-- -------------------------------------------------------------
-- vw_apoiadores_ativos
-- Listagem de apoiadores com dados do líder vinculado.
-- -------------------------------------------------------------
CREATE OR REPLACE VIEW sige.vw_apoiadores_ativos
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
WHERE a.excluido_em IS NULL;

COMMENT ON VIEW sige.vw_apoiadores_ativos
    IS 'Apoiadores ativos com dados do líder vinculado — soft delete aplicado em ambas as tabelas';

-- =============================================================
-- VIEWS DE RELATÓRIOS
-- Preparadas para consumo direto pelo módulo de relatórios
-- e futuro dashboard / BI.
-- =============================================================

-- -------------------------------------------------------------
-- vw_relatorio_resumo
-- Painel geral: totais e médias do sistema.
-- Consumida pelo endpoint GET /relatorios/resumo
-- -------------------------------------------------------------
CREATE OR REPLACE VIEW sige.vw_relatorio_resumo AS
SELECT
    (SELECT COUNT(*)           FROM sige.lideres    WHERE excluido_em IS NULL)                             AS total_lideres,
    (SELECT COUNT(*)           FROM sige.apoiadores WHERE excluido_em IS NULL)                             AS total_apoiadores,
    (SELECT COALESCE(SUM(votos_estimados), 0) FROM sige.lideres WHERE excluido_em IS NULL)                 AS total_votos_estimados,
    (SELECT COALESCE(ROUND(AVG(votos_estimados), 2), 0) FROM sige.lideres WHERE excluido_em IS NULL)       AS media_votos_por_lider,
    (SELECT COUNT(*) FROM sige.apoiadores WHERE excluido_em IS NULL AND status_politico = 'apoiador')      AS total_apoiadores_confirmados,
    (SELECT COUNT(*) FROM sige.apoiadores WHERE excluido_em IS NULL AND status_politico = 'indeciso')      AS total_indecisos,
    (SELECT COUNT(*) FROM sige.apoiadores WHERE excluido_em IS NULL AND status_politico = 'oposicao')      AS total_oposicao,
    NOW() AS gerado_em;

COMMENT ON VIEW sige.vw_relatorio_resumo
    IS 'Resumo geral do sistema: totais e médias para o painel principal';

-- -------------------------------------------------------------
-- vw_relatorio_por_lider
-- Breakdown por líder: contagens e percentuais de status.
-- Consumida pelo endpoint GET /relatorios/por-lider
-- e pelo ranking de líderes.
-- -------------------------------------------------------------
CREATE OR REPLACE VIEW sige.vw_relatorio_por_lider AS
SELECT
    l.id                                                                         AS lider_id,
    l.nome                                                                       AS lider_nome,
    l.bairro                                                                     AS lider_bairro,
    l.votos_estimados,
    COUNT(a.id)                                                                  AS total_apoiadores,
    COUNT(a.id) FILTER (WHERE a.status_politico = 'apoiador')                   AS qtd_apoiadores,
    COUNT(a.id) FILTER (WHERE a.status_politico = 'indeciso')                   AS qtd_indecisos,
    COUNT(a.id) FILTER (WHERE a.status_politico = 'oposicao')                   AS qtd_oposicao,
    CASE
        WHEN COUNT(a.id) > 0
        THEN ROUND(COUNT(a.id) FILTER (WHERE a.status_politico = 'apoiador') * 100.0 / COUNT(a.id), 1)
        ELSE 0
    END                                                                          AS perc_apoiadores,
    CASE
        WHEN COUNT(a.id) > 0
        THEN ROUND(COUNT(a.id) FILTER (WHERE a.status_politico = 'indeciso') * 100.0 / COUNT(a.id), 1)
        ELSE 0
    END                                                                          AS perc_indecisos,
    CASE
        WHEN COUNT(a.id) > 0
        THEN ROUND(COUNT(a.id) FILTER (WHERE a.status_politico = 'oposicao') * 100.0 / COUNT(a.id), 1)
        ELSE 0
    END                                                                          AS perc_oposicao,
    DENSE_RANK() OVER (ORDER BY l.votos_estimados DESC)                         AS ranking_votos,
    DENSE_RANK() OVER (ORDER BY COUNT(a.id) DESC)                               AS ranking_apoiadores
FROM sige.lideres l
LEFT JOIN sige.apoiadores a
    ON a.lider_id = l.id AND a.excluido_em IS NULL
WHERE l.excluido_em IS NULL
GROUP BY l.id, l.nome, l.bairro, l.votos_estimados;

COMMENT ON VIEW sige.vw_relatorio_por_lider
    IS 'Breakdown por líder com totais, percentuais e ranking — para relatório e dashboard';

-- -------------------------------------------------------------
-- vw_relatorio_por_bairro
-- Consolidação territorial: agrega líderes e apoiadores
-- por bairro/região para mapas e heatmaps futuros.
-- Consumida pelo endpoint GET /relatorios/por-bairro
-- -------------------------------------------------------------
CREATE OR REPLACE VIEW sige.vw_relatorio_por_bairro AS
SELECT
    COALESCE(bairro, '(sem bairro)')                                            AS bairro,
    'lider'                                                                      AS tipo,
    COUNT(*)                                                                     AS total,
    COALESCE(SUM(votos_estimados), 0)                                           AS votos_estimados,
    NULL::bigint                                                                 AS qtd_apoiadores,
    NULL::bigint                                                                 AS qtd_indecisos,
    NULL::bigint                                                                 AS qtd_oposicao
FROM sige.lideres
WHERE excluido_em IS NULL
GROUP BY bairro

UNION ALL

SELECT
    COALESCE(bairro, '(sem bairro)')                                            AS bairro,
    'apoiador'                                                                   AS tipo,
    COUNT(*)                                                                     AS total,
    0                                                                            AS votos_estimados,
    COUNT(*) FILTER (WHERE status_politico = 'apoiador')                        AS qtd_apoiadores,
    COUNT(*) FILTER (WHERE status_politico = 'indeciso')                        AS qtd_indecisos,
    COUNT(*) FILTER (WHERE status_politico = 'oposicao')                        AS qtd_oposicao
FROM sige.apoiadores
WHERE excluido_em IS NULL
GROUP BY bairro;

COMMENT ON VIEW sige.vw_relatorio_por_bairro
    IS 'Consolidação por bairro/região de líderes e apoiadores — preparado para mapas e heatmaps';

-- -------------------------------------------------------------
-- vw_relatorio_consolidado
-- Relatório político consolidado: visão única por líder
-- com todos os dados relevantes para exportação e BI.
-- Consumida pelo endpoint GET /relatorios/consolidado
-- -------------------------------------------------------------
CREATE OR REPLACE VIEW sige.vw_relatorio_consolidado AS
SELECT
    l.id                                                                         AS lider_id,
    l.nome                                                                       AS lider_nome,
    l.bairro                                                                     AS lider_bairro,
    l.votos_estimados,
    l.status                                                                     AS lider_ativo,
    l.criado_em                                                                  AS lider_cadastrado_em,
    COUNT(a.id)                                                                  AS total_vinculados,
    COUNT(a.id) FILTER (WHERE a.status_politico = 'apoiador')                   AS apoiadores,
    COUNT(a.id) FILTER (WHERE a.status_politico = 'indeciso')                   AS indecisos,
    COUNT(a.id) FILTER (WHERE a.status_politico = 'oposicao')                   AS oposicao,
    l.votos_estimados + COUNT(a.id) FILTER (WHERE a.status_politico = 'apoiador') AS potencial_total_votos,
    DENSE_RANK() OVER (ORDER BY l.votos_estimados + COUNT(a.id) FILTER (WHERE a.status_politico = 'apoiador') DESC) AS posicao_ranking
FROM sige.lideres l
LEFT JOIN sige.apoiadores a
    ON a.lider_id = l.id AND a.excluido_em IS NULL
WHERE l.excluido_em IS NULL
GROUP BY l.id, l.nome, l.bairro, l.votos_estimados, l.status, l.criado_em;

COMMENT ON VIEW sige.vw_relatorio_consolidado
    IS 'Relatório político consolidado por líder com potencial total de votos e ranking final';
