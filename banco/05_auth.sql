-- =============================================================
-- SIGE - Script 05: Suporte a autenticação via CPF
-- =============================================================
-- Adiciona campos de CPF + senha a usuarios e senha a lideres.
-- Cria view unificada vw_credenciais para o login.
-- =============================================================

SET search_path TO sige, public;

-- ----------------------------------------------------------
-- 1. Adiciona CPF e hash de CPF à tabela usuarios
--    (admins/coordenadores também autenticam via CPF)
-- ----------------------------------------------------------
ALTER TABLE sige.usuarios
    ADD COLUMN IF NOT EXISTS cpf      TEXT         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS cpf_hash VARCHAR(64)  DEFAULT NULL;

COMMENT ON COLUMN sige.usuarios.cpf      IS 'CPF criptografado AES-256 pela aplicação - LGPD';
COMMENT ON COLUMN sige.usuarios.cpf_hash IS 'HMAC-SHA256 do CPF — exclusividade de login';

CREATE UNIQUE INDEX IF NOT EXISTS uq_usuarios_cpf_hash
    ON sige.usuarios (cpf_hash)
    WHERE cpf_hash IS NOT NULL AND excluido_em IS NULL;

-- ----------------------------------------------------------
-- 2. Adiciona senha à tabela lideres
--    Só líderes com senha cadastrada podem fazer login
-- ----------------------------------------------------------
ALTER TABLE sige.lideres
    ADD COLUMN IF NOT EXISTS senha VARCHAR(255) DEFAULT NULL;

COMMENT ON COLUMN sige.lideres.senha IS 'Hash bcrypt — NULL significa líder sem acesso ao sistema';

-- ----------------------------------------------------------
-- 3. View unificada para login via CPF
--    Combina usuarios (admin/coordenador) e lideres em
--    uma única relação sem expor dados sensíveis extras.
-- ----------------------------------------------------------
DROP VIEW IF EXISTS sige.vw_credenciais CASCADE;

CREATE VIEW sige.vw_credenciais
    WITH (security_barrier = true)
AS
SELECT
    u.id,
    u.nome,
    u.cpf_hash,
    u.senha     AS senha_hash,
    u.perfil::text AS perfil,
    'usuario'   AS tipo
FROM sige.usuarios u
WHERE u.excluido_em IS NULL
  AND u.status = TRUE
  AND u.cpf_hash IS NOT NULL
  AND u.senha    IS NOT NULL

UNION ALL

SELECT
    l.id,
    l.nome,
    l.cpf_hash,
    l.senha     AS senha_hash,
    'lider'     AS perfil,
    'lider'     AS tipo
FROM sige.lideres l
WHERE l.excluido_em IS NULL
  AND l.status = TRUE
  AND l.senha  IS NOT NULL;

COMMENT ON VIEW sige.vw_credenciais IS 'View unificada de credenciais para autenticação via CPF';
