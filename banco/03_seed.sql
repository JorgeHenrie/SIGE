-- =============================================================
-- SIGE - Sistema de Mapeamento Político
-- Script 03: Dados iniciais (seed)
-- =============================================================
-- Execução: psql -h localhost -U postgres -d sige_db -f 03_seed.sql
-- =============================================================
-- ATENÇÃO: A senha abaixo é um hash bcrypt de 'Admin@2026'
-- Deve ser alterada no primeiro acesso ao sistema.
-- Hash gerado com bcrypt, custo 12.
-- =============================================================

SET search_path TO sige, public;

INSERT INTO sige.usuarios (
    nome,
    email,
    senha,
    perfil,
    status
)
VALUES (
    'Administrador',
    'admin@sige.local',
    '$2y$12$TKh8H1.PfYi1.y.Y.k0rIO.eXkGhP9kSwgNbFQWbRMcSo0T6q0.Im',
    'admin',
    TRUE
)
ON CONFLICT (email) DO NOTHING;

-- =============================================================
-- Verificação
-- =============================================================
SELECT
    id,
    nome,
    email,
    perfil,
    status,
    criado_em
FROM sige.usuarios
WHERE excluido_em IS NULL;
