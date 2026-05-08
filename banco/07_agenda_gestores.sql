-- =============================================================
-- SIGE - Script 07: Perfil gestor + módulo de agenda
-- =============================================================
-- Execução: psql -h localhost -U postgres -d sige_db -f 07_agenda_gestores.sql
-- =============================================================

SET search_path TO sige, public;

-- -------------------------------------------------------------
-- Perfil gestor
-- -------------------------------------------------------------
ALTER TYPE sige.perfil_usuario ADD VALUE IF NOT EXISTS 'gestor';

-- -------------------------------------------------------------
-- Tipos para agenda
-- -------------------------------------------------------------
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_type t
        INNER JOIN pg_namespace n ON n.oid = t.typnamespace
        WHERE n.nspname = 'sige'
          AND t.typname = 'tipo_agenda_enum'
    ) THEN
        CREATE TYPE sige.tipo_agenda_enum AS ENUM ('visita', 'reuniao', 'outro');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_type t
        INNER JOIN pg_namespace n ON n.oid = t.typnamespace
        WHERE n.nspname = 'sige'
          AND t.typname = 'status_agenda_enum'
    ) THEN
        CREATE TYPE sige.status_agenda_enum AS ENUM ('pendente', 'aprovado', 'recusado');
    END IF;
END $$;

-- -------------------------------------------------------------
-- TABELA: agenda_eventos
-- Líder solicita e gestor/admin decide a agenda do deputado.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sige.agenda_eventos (
    id                      UUID                         NOT NULL DEFAULT gen_random_uuid(),
    lider_id                UUID                         NOT NULL,
    criado_por_usuario_id   UUID                                  DEFAULT NULL,
    titulo                  VARCHAR(160)                 NOT NULL,
    tipo                    sige.tipo_agenda_enum        NOT NULL DEFAULT 'reuniao',
    descricao               TEXT                                  DEFAULT NULL,
    local_evento            VARCHAR(180)                          DEFAULT NULL,
    data_solicitada_inicio  TIMESTAMP WITHOUT TIME ZONE  NOT NULL,
    data_solicitada_fim     TIMESTAMP WITHOUT TIME ZONE           DEFAULT NULL,
    data_confirmada_inicio  TIMESTAMP WITHOUT TIME ZONE           DEFAULT NULL,
    data_confirmada_fim     TIMESTAMP WITHOUT TIME ZONE           DEFAULT NULL,
    status                  sige.status_agenda_enum      NOT NULL DEFAULT 'pendente',
    observacoes_solicitacao TEXT                                  DEFAULT NULL,
    observacoes_decisao     TEXT                                  DEFAULT NULL,
    decidido_por            UUID                                  DEFAULT NULL,
    decidido_em             TIMESTAMP WITHOUT TIME ZONE           DEFAULT NULL,
    criado_em               TIMESTAMP WITHOUT TIME ZONE  NOT NULL DEFAULT NOW(),
    atualizado_em           TIMESTAMP WITHOUT TIME ZONE  NOT NULL DEFAULT NOW(),
    excluido_em             TIMESTAMP WITHOUT TIME ZONE           DEFAULT NULL,

    CONSTRAINT pk_agenda_eventos PRIMARY KEY (id),
    CONSTRAINT fk_agenda_eventos_lider FOREIGN KEY (lider_id)
        REFERENCES sige.lideres(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_agenda_eventos_criado_por FOREIGN KEY (criado_por_usuario_id)
        REFERENCES sige.usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_agenda_eventos_decidido_por FOREIGN KEY (decidido_por)
        REFERENCES sige.usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT ck_agenda_eventos_intervalo_solicitado CHECK (
        data_solicitada_fim IS NULL OR data_solicitada_fim >= data_solicitada_inicio
    ),
    CONSTRAINT ck_agenda_eventos_intervalo_confirmado CHECK (
        data_confirmada_inicio IS NULL OR data_confirmada_fim IS NULL OR data_confirmada_fim >= data_confirmada_inicio
    ),
    CONSTRAINT ck_agenda_eventos_status_decisao CHECK (
        (
            status = 'pendente'
            AND decidido_por IS NULL
            AND decidido_em IS NULL
            AND data_confirmada_inicio IS NULL
            AND data_confirmada_fim IS NULL
        )
        OR
        (
            status = 'aprovado'
            AND decidido_por IS NOT NULL
            AND decidido_em IS NOT NULL
            AND data_confirmada_inicio IS NOT NULL
        )
        OR
        (
            status = 'recusado'
            AND decidido_por IS NOT NULL
            AND decidido_em IS NOT NULL
            AND data_confirmada_inicio IS NULL
            AND data_confirmada_fim IS NULL
        )
    )
);

COMMENT ON TABLE sige.agenda_eventos
    IS 'Solicitações de agenda criadas por líderes e decididas por gestor/admin';

COMMENT ON COLUMN sige.agenda_eventos.criado_por_usuario_id
    IS 'Usuário autenticado que abriu a solicitação quando não vier diretamente do login do líder';

COMMENT ON COLUMN sige.agenda_eventos.decidido_por
    IS 'Gestor/admin que aprovou ou recusou a solicitação';

CREATE INDEX IF NOT EXISTS ix_agenda_eventos_lider_id
    ON sige.agenda_eventos(lider_id)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_agenda_eventos_status
    ON sige.agenda_eventos(status)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_agenda_eventos_data_solicitada_inicio
    ON sige.agenda_eventos(data_solicitada_inicio)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_agenda_eventos_decidido_por
    ON sige.agenda_eventos(decidido_por)
    WHERE excluido_em IS NULL;

DROP VIEW IF EXISTS sige.vw_agenda_eventos CASCADE;

CREATE VIEW sige.vw_agenda_eventos
    WITH (security_barrier = true)
AS
SELECT
    a.id,
    a.titulo,
    a.tipo::text                    AS tipo,
    a.descricao,
    a.local_evento,
    a.data_solicitada_inicio,
    a.data_solicitada_fim,
    a.data_confirmada_inicio,
    a.data_confirmada_fim,
    a.status::text                  AS status,
    a.observacoes_solicitacao,
    a.observacoes_decisao,
    a.decidido_por,
    a.decidido_em,
    a.criado_por_usuario_id,
    a.criado_em,
    a.atualizado_em,
    l.id                            AS lider_id,
    l.nome                          AS lider_nome,
    l.bairro                        AS lider_bairro,
    uc.nome                         AS criado_por_usuario_nome,
    ud.nome                         AS decidido_por_nome
FROM sige.agenda_eventos a
INNER JOIN sige.lideres l
    ON l.id = a.lider_id AND l.excluido_em IS NULL
LEFT JOIN sige.usuarios uc
    ON uc.id = a.criado_por_usuario_id AND uc.excluido_em IS NULL
LEFT JOIN sige.usuarios ud
    ON ud.id = a.decidido_por AND ud.excluido_em IS NULL
WHERE a.excluido_em IS NULL;

COMMENT ON VIEW sige.vw_agenda_eventos
    IS 'Eventos de agenda com líder solicitante e gestor/admin decisor';

DROP TRIGGER IF EXISTS trg_agenda_eventos_atualizado_em ON sige.agenda_eventos;

CREATE TRIGGER trg_agenda_eventos_atualizado_em
    BEFORE UPDATE ON sige.agenda_eventos
    FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();