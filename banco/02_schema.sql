-- =============================================================
-- SIGE - Sistema de Mapeamento Político
-- Script 02: Schema, tipos, tabelas, índices, triggers
-- =============================================================
-- Execução: psql -h localhost -U postgres -d sige_db -f 02_schema.sql
-- =============================================================

-- -------------------------------------------------------------
-- EXTENSÕES
-- -------------------------------------------------------------
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- -------------------------------------------------------------
-- SCHEMA PRINCIPAL
-- -------------------------------------------------------------
CREATE SCHEMA IF NOT EXISTS sige AUTHORIZATION postgres;

COMMENT ON SCHEMA sige IS 'Schema principal do SIGE - Sistema de Mapeamento Político';

SET search_path TO sige, public;

-- -------------------------------------------------------------
-- TIPOS ENUMERADOS
-- -------------------------------------------------------------

CREATE TYPE sige.perfil_usuario AS ENUM (
    'admin',
    'coordenador',
    'lider',
    'supervisor'
);

CREATE TYPE sige.status_politico_enum AS ENUM (
    'apoiador',
    'indeciso',
    'oposicao'
);

-- -------------------------------------------------------------
-- TABELA: usuarios
-- Representa os usuários autenticados do sistema.
-- Inicialmente apenas admin. Preparado para múltiplos perfis.
-- -------------------------------------------------------------
CREATE TABLE sige.usuarios (
    id            UUID                        NOT NULL DEFAULT gen_random_uuid(),
    nome          VARCHAR(150)                NOT NULL,
    email         VARCHAR(150)                NOT NULL,
    senha         VARCHAR(255)                NOT NULL,
    perfil        sige.perfil_usuario         NOT NULL DEFAULT 'admin',
    status        BOOLEAN                     NOT NULL DEFAULT TRUE,
    criado_em     TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    atualizado_em TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    excluido_em   TIMESTAMP WITHOUT TIME ZONE          DEFAULT NULL,

    CONSTRAINT pk_usuarios       PRIMARY KEY (id),
    CONSTRAINT uq_usuarios_email UNIQUE (email)
);

COMMENT ON TABLE  sige.usuarios              IS 'Usuários autenticados do sistema';
COMMENT ON COLUMN sige.usuarios.id           IS 'UUID gerado automaticamente - evita enumeração de IDs';
COMMENT ON COLUMN sige.usuarios.senha        IS 'Hash argon2id/bcrypt - NUNCA armazenar senha em texto puro';
COMMENT ON COLUMN sige.usuarios.perfil       IS 'Perfil de acesso: admin, coordenador, lider, supervisor';
COMMENT ON COLUMN sige.usuarios.excluido_em  IS 'Soft delete - LGPD: direito ao esquecimento / portabilidade';

-- -------------------------------------------------------------
-- TABELA: lideres
-- Representa lideranças políticas com influência eleitoral.
-- Relacionamento: usuarios 1:N lideres (criado_por)
-- Relacionamento: lideres  1:N apoiadores
-- -------------------------------------------------------------
CREATE TABLE sige.lideres (
    id              UUID                        NOT NULL DEFAULT gen_random_uuid(),
    nome            VARCHAR(150)                NOT NULL,
    cpf             TEXT                        NOT NULL,
    cpf_hash        VARCHAR(64)                 NOT NULL,
    telefone        VARCHAR(20)                          DEFAULT NULL,
    bairro          VARCHAR(100)                         DEFAULT NULL,
    votos_estimados INTEGER                     NOT NULL DEFAULT 0,
    observacoes     TEXT                                 DEFAULT NULL,
    status          BOOLEAN                     NOT NULL DEFAULT TRUE,
    criado_por      UUID                                 DEFAULT NULL,
    criado_em       TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    atualizado_em   TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    excluido_em     TIMESTAMP WITHOUT TIME ZONE          DEFAULT NULL,

    CONSTRAINT pk_lideres            PRIMARY KEY (id),
    CONSTRAINT uq_lideres_cpf_hash   UNIQUE (cpf_hash),
    CONSTRAINT ck_lideres_votos      CHECK (votos_estimados >= 0),
    CONSTRAINT fk_lideres_criado_por FOREIGN KEY (criado_por)
        REFERENCES sige.usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

COMMENT ON TABLE  sige.lideres                  IS 'Lideranças políticas cadastradas no sistema';
COMMENT ON COLUMN sige.lideres.id               IS 'UUID gerado automaticamente - evita enumeração de IDs';
COMMENT ON COLUMN sige.lideres.cpf              IS 'CPF criptografado AES-256 pela aplicação - LGPD dado pessoal Art. 5';
COMMENT ON COLUMN sige.lideres.cpf_hash         IS 'HMAC-SHA256 do CPF para verificação de unicidade - não reversível';
COMMENT ON COLUMN sige.lideres.votos_estimados  IS 'Estimativa declarada de votos sob influência do líder';
COMMENT ON COLUMN sige.lideres.criado_por       IS 'FK para usuários: rastreabilidade de quem cadastrou o líder';
COMMENT ON COLUMN sige.lideres.excluido_em      IS 'Soft delete - LGPD: direito ao esquecimento / portabilidade';

-- -------------------------------------------------------------
-- TABELA: apoiadores
-- Pessoas vinculadas politicamente a um líder.
-- Dado sensível conforme LGPD Art. 11 (opinião política).
-- Relacionamento: lideres 1:N apoiadores
-- -------------------------------------------------------------
CREATE TABLE sige.apoiadores (
    id              UUID                        NOT NULL DEFAULT gen_random_uuid(),
    lider_id        UUID                        NOT NULL,
    nome            VARCHAR(150)                NOT NULL,
    cpf             TEXT                        NOT NULL,
    cpf_hash        VARCHAR(64)                 NOT NULL,
    telefone        VARCHAR(20)                          DEFAULT NULL,
    bairro          VARCHAR(100)                         DEFAULT NULL,
    status_politico sige.status_politico_enum   NOT NULL DEFAULT 'indeciso',
    observacoes     TEXT                                 DEFAULT NULL,
    criado_por      UUID                                 DEFAULT NULL,
    criado_em       TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    atualizado_em   TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    excluido_em     TIMESTAMP WITHOUT TIME ZONE          DEFAULT NULL,

    CONSTRAINT pk_apoiadores            PRIMARY KEY (id),
    CONSTRAINT uq_apoiadores_cpf_hash   UNIQUE (cpf_hash),
    CONSTRAINT fk_apoiadores_lider      FOREIGN KEY (lider_id)
        REFERENCES sige.lideres(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_apoiadores_criado_por FOREIGN KEY (criado_por)
        REFERENCES sige.usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

COMMENT ON TABLE  sige.apoiadores                  IS 'Pessoas vinculadas politicamente a um líder';
COMMENT ON COLUMN sige.apoiadores.id               IS 'UUID gerado automaticamente - evita enumeração de IDs';
COMMENT ON COLUMN sige.apoiadores.lider_id         IS 'FK obrigatória: todo apoiador pertence a exatamente um líder';
COMMENT ON COLUMN sige.apoiadores.cpf              IS 'CPF criptografado AES-256 pela aplicação - LGPD dado pessoal Art. 5';
COMMENT ON COLUMN sige.apoiadores.cpf_hash         IS 'HMAC-SHA256 do CPF para verificação de unicidade - não reversível';
COMMENT ON COLUMN sige.apoiadores.status_politico  IS 'Dado sensível conforme LGPD Art. 11 - base legal: legítimo interesse de campanha';
COMMENT ON COLUMN sige.apoiadores.criado_por       IS 'FK para usuários: rastreabilidade de quem cadastrou o apoiador';
COMMENT ON COLUMN sige.apoiadores.excluido_em      IS 'Soft delete - LGPD: direito ao esquecimento / portabilidade';

-- -------------------------------------------------------------
-- TABELA: tokens_refresh
-- Scaffolded para autenticação JWT futura.
-- Armazena apenas o hash do token, nunca o token bruto.
-- -------------------------------------------------------------
CREATE TABLE sige.tokens_refresh (
    id          UUID                        NOT NULL DEFAULT gen_random_uuid(),
    usuario_id  UUID                        NOT NULL,
    token_hash  VARCHAR(64)                 NOT NULL,
    ip_origem   VARCHAR(45)                          DEFAULT NULL,
    user_agent  TEXT                                 DEFAULT NULL,
    expira_em   TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    revogado    BOOLEAN                     NOT NULL DEFAULT FALSE,
    criado_em   TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),

    CONSTRAINT pk_tokens_refresh      PRIMARY KEY (id),
    CONSTRAINT uq_tokens_hash         UNIQUE (token_hash),
    CONSTRAINT fk_tokens_usuario      FOREIGN KEY (usuario_id)
        REFERENCES sige.usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

COMMENT ON TABLE  sige.tokens_refresh            IS 'Refresh tokens para autenticação JWT - scaffolded para expansão futura';
COMMENT ON COLUMN sige.tokens_refresh.token_hash IS 'SHA-256 do token bruto - token jamais armazenado em texto puro';
COMMENT ON COLUMN sige.tokens_refresh.ip_origem  IS 'IP de origem para auditoria e detecção de anomalias';

-- -------------------------------------------------------------
-- TABELA: logs_auditoria
-- Trilha de auditoria para LGPD e rastreabilidade.
-- Preparada para dashboard de auditoria futuro.
-- -------------------------------------------------------------
CREATE TABLE sige.logs_auditoria (
    id               UUID                        NOT NULL DEFAULT gen_random_uuid(),
    usuario_id       UUID                                 DEFAULT NULL,
    tabela           VARCHAR(100)                NOT NULL,
    operacao         VARCHAR(10)                 NOT NULL,
    registro_id      UUID                                 DEFAULT NULL,
    dados_anteriores JSONB                                DEFAULT NULL,
    dados_novos      JSONB                                DEFAULT NULL,
    ip_origem        VARCHAR(45)                          DEFAULT NULL,
    criado_em        TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),

    CONSTRAINT pk_logs_auditoria  PRIMARY KEY (id),
    CONSTRAINT fk_logs_usuario    FOREIGN KEY (usuario_id)
        REFERENCES sige.usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT ck_logs_operacao   CHECK (
        operacao IN ('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'ACCESS')
    )
);

COMMENT ON TABLE  sige.logs_auditoria                  IS 'Trilha de auditoria completa - LGPD rastreabilidade e conformidade';
COMMENT ON COLUMN sige.logs_auditoria.dados_anteriores IS 'Estado anterior do registro em JSON - para rollback e auditoria';
COMMENT ON COLUMN sige.logs_auditoria.dados_novos      IS 'Estado novo do registro em JSON - para auditoria';
COMMENT ON COLUMN sige.logs_auditoria.operacao         IS 'Tipo de operação: INSERT, UPDATE, DELETE, LOGIN, LOGOUT, ACCESS';

-- =============================================================
-- ÍNDICES
-- Todos os índices parciais usam WHERE excluido_em IS NULL
-- para ignorar registros deletados e manter performance.
-- =============================================================

-- usuarios
CREATE UNIQUE INDEX uix_usuarios_email_ativo
    ON sige.usuarios(email)
    WHERE excluido_em IS NULL;

CREATE INDEX ix_usuarios_perfil
    ON sige.usuarios(perfil)
    WHERE excluido_em IS NULL;

CREATE INDEX ix_usuarios_status
    ON sige.usuarios(status)
    WHERE excluido_em IS NULL;

-- lideres
CREATE UNIQUE INDEX uix_lideres_cpf_hash_ativo
    ON sige.lideres(cpf_hash)
    WHERE excluido_em IS NULL;

CREATE INDEX ix_lideres_bairro
    ON sige.lideres(bairro)
    WHERE excluido_em IS NULL;

CREATE INDEX ix_lideres_status
    ON sige.lideres(status)
    WHERE excluido_em IS NULL;

CREATE INDEX ix_lideres_criado_por
    ON sige.lideres(criado_por);

CREATE INDEX ix_lideres_votos_estimados_rank
    ON sige.lideres(votos_estimados DESC)
    WHERE excluido_em IS NULL;

-- apoiadores
CREATE UNIQUE INDEX uix_apoiadores_cpf_hash_ativo
    ON sige.apoiadores(cpf_hash)
    WHERE excluido_em IS NULL;

CREATE INDEX ix_apoiadores_lider_id
    ON sige.apoiadores(lider_id)
    WHERE excluido_em IS NULL;

CREATE INDEX ix_apoiadores_status_politico
    ON sige.apoiadores(status_politico)
    WHERE excluido_em IS NULL;

CREATE INDEX ix_apoiadores_bairro
    ON sige.apoiadores(bairro)
    WHERE excluido_em IS NULL;

-- Índice composto: JOIN de relatórios (lider + status em uma única varredura)
CREATE INDEX ix_apoiadores_lider_status_politico
    ON sige.apoiadores(lider_id, status_politico)
    WHERE excluido_em IS NULL;

-- tokens_refresh
CREATE INDEX ix_tokens_usuario_id
    ON sige.tokens_refresh(usuario_id);

CREATE INDEX ix_tokens_expira_em_ativos
    ON sige.tokens_refresh(expira_em)
    WHERE revogado = FALSE;

-- logs_auditoria
CREATE INDEX ix_logs_usuario_id
    ON sige.logs_auditoria(usuario_id);

CREATE INDEX ix_logs_tabela_operacao
    ON sige.logs_auditoria(tabela, operacao);

CREATE INDEX ix_logs_criado_em_desc
    ON sige.logs_auditoria(criado_em DESC);

CREATE INDEX ix_logs_registro_id
    ON sige.logs_auditoria(registro_id)
    WHERE registro_id IS NOT NULL;

-- =============================================================
-- FUNÇÃO: atualizar timestamp automaticamente
-- Evita inconsistências quando a aplicação esquece de atualizar
-- =============================================================
CREATE OR REPLACE FUNCTION sige.fn_atualizar_timestamp()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.atualizado_em = NOW();
    RETURN NEW;
END;
$$;

COMMENT ON FUNCTION sige.fn_atualizar_timestamp()
    IS 'Atualiza automaticamente atualizado_em antes de qualquer UPDATE';

-- Triggers
CREATE TRIGGER trg_usuarios_atualizado_em
    BEFORE UPDATE ON sige.usuarios
    FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();

CREATE TRIGGER trg_lideres_atualizado_em
    BEFORE UPDATE ON sige.lideres
    FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();

CREATE TRIGGER trg_apoiadores_atualizado_em
    BEFORE UPDATE ON sige.apoiadores
    FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();
