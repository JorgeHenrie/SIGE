-- =============================================================
-- SIGE - Script 17: Roteirizacao inteligente de campanha
-- =============================================================
-- Execucao: psql -h localhost -U postgres -d sige_db -f 17_roteirizacao_inteligente.sql
-- =============================================================

SET search_path TO sige, public;

-- -------------------------------------------------------------
-- Tipos do modulo
-- -------------------------------------------------------------
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_type t
        INNER JOIN pg_namespace n ON n.oid = t.typnamespace
        WHERE n.nspname = 'sige'
          AND t.typname = 'transporte_roteiro_enum'
    ) THEN
        CREATE TYPE sige.transporte_roteiro_enum AS ENUM ('carro', 'moto', 'a_pe');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_type t
        INNER JOIN pg_namespace n ON n.oid = t.typnamespace
        WHERE n.nspname = 'sige'
          AND t.typname = 'prioridade_roteiro_enum'
    ) THEN
        CREATE TYPE sige.prioridade_roteiro_enum AS ENUM ('alta', 'media', 'baixa');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_type t
        INNER JOIN pg_namespace n ON n.oid = t.typnamespace
        WHERE n.nspname = 'sige'
          AND t.typname = 'status_roteiro_enum'
    ) THEN
        CREATE TYPE sige.status_roteiro_enum AS ENUM ('rascunho', 'processado');
    END IF;
END $$;

-- -------------------------------------------------------------
-- Cache de geocoding
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sige.geocoding_cache (
    id                   UUID                        NOT NULL DEFAULT gen_random_uuid(),
    endereco_original    VARCHAR(255)                NOT NULL,
    endereco_normalizado VARCHAR(255)                NOT NULL,
    latitude             NUMERIC(10, 7)              NOT NULL,
    longitude            NUMERIC(10, 7)              NOT NULL,
    provider             VARCHAR(40)                 NOT NULL DEFAULT 'nominatim',
    provider_place_id    VARCHAR(120)                         DEFAULT NULL,
    score_confianca      NUMERIC(5, 2)                        DEFAULT NULL,
    metadados            JSONB                                DEFAULT NULL,
    criado_em            TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    atualizado_em        TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),

    CONSTRAINT pk_geocoding_cache PRIMARY KEY (id),
    CONSTRAINT uq_geocoding_cache_endereco UNIQUE (endereco_normalizado, provider)
);

COMMENT ON TABLE sige.geocoding_cache
    IS 'Cache local de geocoding para reduzir custo e latencia do modulo de roteirizacao';

COMMENT ON COLUMN sige.geocoding_cache.score_confianca
    IS 'Pontuacao de confianca do provedor, quando disponivel';

CREATE INDEX IF NOT EXISTS ix_geocoding_cache_provider
    ON sige.geocoding_cache(provider);

CREATE INDEX IF NOT EXISTS ix_geocoding_cache_endereco_normalizado
    ON sige.geocoding_cache(endereco_normalizado);

DROP TRIGGER IF EXISTS trg_geocoding_cache_atualizado_em ON sige.geocoding_cache;

CREATE TRIGGER trg_geocoding_cache_atualizado_em
    BEFORE UPDATE ON sige.geocoding_cache
    FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();

-- -------------------------------------------------------------
-- Roteiros do dia
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sige.roteiros_campanha (
    id                       UUID                         NOT NULL DEFAULT gen_random_uuid(),
    lider_id                 UUID                         NOT NULL,
    criado_por_usuario_id    UUID                                  DEFAULT NULL,
    data_roteiro             DATE                         NOT NULL,
    local_saida              VARCHAR(180)                 NOT NULL,
    local_saida_latitude     NUMERIC(10, 7)                        DEFAULT NULL,
    local_saida_longitude    NUMERIC(10, 7)                        DEFAULT NULL,
    transporte               sige.transporte_roteiro_enum NOT NULL,
    status                   sige.status_roteiro_enum     NOT NULL DEFAULT 'processado',
    raio_cluster_km          NUMERIC(5, 2)                NOT NULL DEFAULT 3.00,
    distancia_total_km       NUMERIC(10, 2)               NOT NULL DEFAULT 0,
    tempo_total_min          INTEGER                      NOT NULL DEFAULT 0,
    custo_estimado           NUMERIC(10, 2)               NOT NULL DEFAULT 0,
    distancia_baseline_km    NUMERIC(10, 2)               NOT NULL DEFAULT 0,
    tempo_baseline_min       INTEGER                      NOT NULL DEFAULT 0,
    economia_km              NUMERIC(10, 2)               NOT NULL DEFAULT 0,
    economia_percentual      NUMERIC(6, 2)                NOT NULL DEFAULT 0,
    sugestao_melhor_roteiro  TEXT                                  DEFAULT NULL,
    logs_decisao_json        JSONB                        NOT NULL DEFAULT '[]'::jsonb,
    criado_em                TIMESTAMP WITHOUT TIME ZONE  NOT NULL DEFAULT NOW(),
    atualizado_em            TIMESTAMP WITHOUT TIME ZONE  NOT NULL DEFAULT NOW(),
    excluido_em              TIMESTAMP WITHOUT TIME ZONE           DEFAULT NULL,

    CONSTRAINT pk_roteiros_campanha PRIMARY KEY (id),
    CONSTRAINT fk_roteiros_campanha_lider FOREIGN KEY (lider_id)
        REFERENCES sige.lideres(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_roteiros_campanha_criado_por FOREIGN KEY (criado_por_usuario_id)
        REFERENCES sige.usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT ck_roteiros_campanha_raio_cluster CHECK (raio_cluster_km >= 0.5 AND raio_cluster_km <= 20),
    CONSTRAINT ck_roteiros_campanha_coordenadas_saida CHECK (
        (local_saida_latitude IS NULL AND local_saida_longitude IS NULL)
        OR (local_saida_latitude IS NOT NULL AND local_saida_longitude IS NOT NULL)
    )
);

COMMENT ON TABLE sige.roteiros_campanha
    IS 'Roteiros diarios sugeridos e persistidos para lideres e gestores';

COMMENT ON COLUMN sige.roteiros_campanha.logs_decisao_json
    IS 'Historico explicavel das decisoes tomadas pelo motor de roteirizacao';

COMMENT ON COLUMN sige.roteiros_campanha.sugestao_melhor_roteiro
    IS 'Resumo textual exibido para o usuario com a recomendacao principal do dia';

CREATE INDEX IF NOT EXISTS ix_roteiros_campanha_lider_id
    ON sige.roteiros_campanha(lider_id)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_roteiros_campanha_data_roteiro
    ON sige.roteiros_campanha(data_roteiro)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_roteiros_campanha_transporte
    ON sige.roteiros_campanha(transporte)
    WHERE excluido_em IS NULL;

DROP TRIGGER IF EXISTS trg_roteiros_campanha_atualizado_em ON sige.roteiros_campanha;

CREATE TRIGGER trg_roteiros_campanha_atualizado_em
    BEFORE UPDATE ON sige.roteiros_campanha
    FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();

-- -------------------------------------------------------------
-- Visitas de cada roteiro
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sige.roteiro_visitas (
    id                       UUID                         NOT NULL DEFAULT gen_random_uuid(),
    roteiro_id               UUID                         NOT NULL,
    agenda_evento_id         UUID                                  DEFAULT NULL,
    apoiador_id              UUID                                  DEFAULT NULL,
    nome                     VARCHAR(160)                 NOT NULL,
    endereco                 VARCHAR(255)                 NOT NULL,
    prioridade               sige.prioridade_roteiro_enum NOT NULL DEFAULT 'media',
    horario_inicio           TIMESTAMP WITHOUT TIME ZONE           DEFAULT NULL,
    horario_fim              TIMESTAMP WITHOUT TIME ZONE           DEFAULT NULL,
    latitude                 NUMERIC(10, 7)              NOT NULL,
    longitude                NUMERIC(10, 7)              NOT NULL,
    cluster_id               INTEGER                      NOT NULL DEFAULT 0,
    ordem_sugerida           INTEGER                      NOT NULL DEFAULT 0,
    desvio_relevante         BOOLEAN                      NOT NULL DEFAULT FALSE,
    motivo_desvio            TEXT                                  DEFAULT NULL,
    distancia_incremental_km NUMERIC(10, 2)               NOT NULL DEFAULT 0,
    tempo_incremental_min    INTEGER                      NOT NULL DEFAULT 0,
    criado_em                TIMESTAMP WITHOUT TIME ZONE  NOT NULL DEFAULT NOW(),
    atualizado_em            TIMESTAMP WITHOUT TIME ZONE  NOT NULL DEFAULT NOW(),

    CONSTRAINT pk_roteiro_visitas PRIMARY KEY (id),
    CONSTRAINT fk_roteiro_visitas_roteiro FOREIGN KEY (roteiro_id)
        REFERENCES sige.roteiros_campanha(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_roteiro_visitas_agenda FOREIGN KEY (agenda_evento_id)
        REFERENCES sige.agenda_eventos(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_roteiro_visitas_apoiador FOREIGN KEY (apoiador_id)
        REFERENCES sige.apoiadores(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT ck_roteiro_visitas_horario CHECK (
        horario_inicio IS NULL OR horario_fim IS NULL OR horario_fim >= horario_inicio
    ),
    CONSTRAINT ck_roteiro_visitas_ordem CHECK (ordem_sugerida >= 0),
    CONSTRAINT ck_roteiro_visitas_cluster CHECK (cluster_id >= 0)
);

COMMENT ON TABLE sige.roteiro_visitas
    IS 'Paradas individuais que compoem o roteiro sugerido';

COMMENT ON COLUMN sige.roteiro_visitas.desvio_relevante
    IS 'Indica se a visita eleva a distancia total do roteiro em mais de 30%';

CREATE INDEX IF NOT EXISTS ix_roteiro_visitas_roteiro_id
    ON sige.roteiro_visitas(roteiro_id);

CREATE INDEX IF NOT EXISTS ix_roteiro_visitas_ordem
    ON sige.roteiro_visitas(roteiro_id, ordem_sugerida);

CREATE INDEX IF NOT EXISTS ix_roteiro_visitas_cluster
    ON sige.roteiro_visitas(roteiro_id, cluster_id);

DROP TRIGGER IF EXISTS trg_roteiro_visitas_atualizado_em ON sige.roteiro_visitas;

CREATE TRIGGER trg_roteiro_visitas_atualizado_em
    BEFORE UPDATE ON sige.roteiro_visitas
    FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();

-- -------------------------------------------------------------
-- Views operacionais
-- -------------------------------------------------------------
DROP VIEW IF EXISTS sige.vw_roteiro_visitas CASCADE;
DROP VIEW IF EXISTS sige.vw_roteiros_campanha CASCADE;

CREATE VIEW sige.vw_roteiros_campanha
    WITH (security_barrier = true)
AS
SELECT
    r.id,
    r.lider_id,
    l.nome                        AS lider_nome,
    l.bairro                      AS lider_bairro,
    r.criado_por_usuario_id,
    u.nome                        AS criado_por_usuario_nome,
    r.data_roteiro,
    r.local_saida,
    r.local_saida_latitude,
    r.local_saida_longitude,
    r.transporte::text            AS transporte,
    r.status::text                AS status,
    r.raio_cluster_km,
    r.distancia_total_km,
    r.tempo_total_min,
    r.custo_estimado,
    r.distancia_baseline_km,
    r.tempo_baseline_min,
    r.economia_km,
    r.economia_percentual,
    r.sugestao_melhor_roteiro,
    r.logs_decisao_json,
    (
        SELECT COUNT(*)
        FROM sige.roteiro_visitas rv
        WHERE rv.roteiro_id = r.id
    )                              AS total_visitas,
    r.criado_em,
    r.atualizado_em
FROM sige.roteiros_campanha r
INNER JOIN sige.lideres l
    ON l.id = r.lider_id AND l.excluido_em IS NULL
LEFT JOIN sige.usuarios u
    ON u.id = r.criado_por_usuario_id AND u.excluido_em IS NULL
WHERE r.excluido_em IS NULL;

COMMENT ON VIEW sige.vw_roteiros_campanha
    IS 'Visao consolidada dos roteiros com dados do lider e resumo operacional';

CREATE VIEW sige.vw_roteiro_visitas
    WITH (security_barrier = true)
AS
SELECT
    rv.id,
    rv.roteiro_id,
    rv.agenda_evento_id,
    rv.apoiador_id,
    rv.nome,
    rv.endereco,
    rv.prioridade::text            AS prioridade,
    rv.horario_inicio,
    rv.horario_fim,
    rv.latitude,
    rv.longitude,
    rv.cluster_id,
    rv.ordem_sugerida,
    rv.desvio_relevante,
    rv.motivo_desvio,
    rv.distancia_incremental_km,
    rv.tempo_incremental_min,
    rv.criado_em,
    rv.atualizado_em,
    a.titulo                       AS agenda_titulo,
    a.local_evento                 AS agenda_local_evento,
    ap.nome                        AS apoiador_nome,
    ap.bairro                      AS apoiador_bairro
FROM sige.roteiro_visitas rv
LEFT JOIN sige.agenda_eventos a
    ON a.id = rv.agenda_evento_id AND a.excluido_em IS NULL
LEFT JOIN sige.apoiadores ap
    ON ap.id = rv.apoiador_id AND ap.excluido_em IS NULL;

COMMENT ON VIEW sige.vw_roteiro_visitas
    IS 'Visao detalhada das visitas vinculadas a cada roteiro';