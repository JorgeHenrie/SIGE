-- =============================================================
-- SIGE - Script 18: Gestao de recursos de campanha e compliance
-- =============================================================
-- Execucao: psql -h localhost -U postgres -d sige_db -f 18_gestao_recursos_campanha.sql
-- =============================================================

SET search_path TO sige, public;

-- -------------------------------------------------------------
-- Tipos do modulo financeiro
-- -------------------------------------------------------------
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_type t
        INNER JOIN pg_namespace n ON n.oid = t.typnamespace
        WHERE n.nspname = 'sige'
          AND t.typname = 'tipo_recurso_campanha_enum'
    ) THEN
        CREATE TYPE sige.tipo_recurso_campanha_enum AS ENUM (
            'fundo_partidario',
            'fundo_eleitoral',
            'doacao_privada'
        );
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_type t
        INNER JOIN pg_namespace n ON n.oid = t.typnamespace
        WHERE n.nspname = 'sige'
          AND t.typname = 'classificacao_conformidade_enum'
    ) THEN
        CREATE TYPE sige.classificacao_conformidade_enum AS ENUM (
            'valida',
            'suspeita',
            'invalida'
        );
    END IF;
END $$;

-- -------------------------------------------------------------
-- Parametros de alertas e compliance
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sige.financeiro_parametros (
    id                                    SMALLINT                     NOT NULL,
    limite_percentual_categoria_excessiva NUMERIC(5, 2)               NOT NULL DEFAULT 40.00,
    limite_percentual_saldo_critico       NUMERIC(5, 2)               NOT NULL DEFAULT 15.00,
    fator_despesa_fora_padrao             NUMERIC(8, 3)               NOT NULL DEFAULT 2.500,
    janela_media_despesa_dias             INTEGER                      NOT NULL DEFAULT 90,
    criado_em                             TIMESTAMP WITHOUT TIME ZONE  NOT NULL DEFAULT NOW(),
    atualizado_em                         TIMESTAMP WITHOUT TIME ZONE  NOT NULL DEFAULT NOW(),

    CONSTRAINT pk_financeiro_parametros PRIMARY KEY (id),
    CONSTRAINT ck_financeiro_parametros_id CHECK (id = 1),
    CONSTRAINT ck_financeiro_parametros_categoria CHECK (
        limite_percentual_categoria_excessiva > 0 AND limite_percentual_categoria_excessiva <= 100
    ),
    CONSTRAINT ck_financeiro_parametros_saldo CHECK (
        limite_percentual_saldo_critico > 0 AND limite_percentual_saldo_critico <= 100
    ),
    CONSTRAINT ck_financeiro_parametros_fator CHECK (fator_despesa_fora_padrao >= 1),
    CONSTRAINT ck_financeiro_parametros_janela CHECK (janela_media_despesa_dias BETWEEN 7 AND 365)
);

INSERT INTO sige.financeiro_parametros (
    id,
    limite_percentual_categoria_excessiva,
    limite_percentual_saldo_critico,
    fator_despesa_fora_padrao,
    janela_media_despesa_dias
)
VALUES (1, 40.00, 15.00, 2.500, 90)
ON CONFLICT (id) DO NOTHING;

DROP TRIGGER IF EXISTS trg_financeiro_parametros_atualizado_em ON sige.financeiro_parametros;

CREATE TRIGGER trg_financeiro_parametros_atualizado_em
    BEFORE UPDATE ON sige.financeiro_parametros
    FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();

-- -------------------------------------------------------------
-- Tabela de fornecedores de campanha
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sige.fornecedores_campanha (
    id                    UUID                         NOT NULL DEFAULT gen_random_uuid(),
    candidato_id          UUID                         NOT NULL,
    nome                  VARCHAR(160)                 NOT NULL,
    documento             VARCHAR(20)                           DEFAULT NULL,
    tipo_fornecedor       VARCHAR(40)                           DEFAULT NULL,
    criado_por_usuario_id UUID                                  DEFAULT NULL,
    criado_em             TIMESTAMP WITHOUT TIME ZONE  NOT NULL DEFAULT NOW(),
    atualizado_em         TIMESTAMP WITHOUT TIME ZONE  NOT NULL DEFAULT NOW(),
    excluido_em           TIMESTAMP WITHOUT TIME ZONE           DEFAULT NULL,

    CONSTRAINT pk_fornecedores_campanha PRIMARY KEY (id),
    CONSTRAINT fk_fornecedores_campanha_candidato FOREIGN KEY (candidato_id)
        REFERENCES sige.lideres(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_fornecedores_campanha_criado_por FOREIGN KEY (criado_por_usuario_id)
        REFERENCES sige.usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS ix_fornecedores_campanha_candidato
    ON sige.fornecedores_campanha(candidato_id)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_fornecedores_campanha_nome
    ON sige.fornecedores_campanha(nome)
    WHERE excluido_em IS NULL;

DROP TRIGGER IF EXISTS trg_fornecedores_campanha_atualizado_em ON sige.fornecedores_campanha;

CREATE TRIGGER trg_fornecedores_campanha_atualizado_em
    BEFORE UPDATE ON sige.fornecedores_campanha
    FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();

-- -------------------------------------------------------------
-- Tabela de receitas de campanha
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sige.receitas_campanha (
    id                    UUID                                  NOT NULL DEFAULT gen_random_uuid(),
    candidato_id          UUID                                  NOT NULL,
    tipo_recurso          sige.tipo_recurso_campanha_enum       NOT NULL,
    valor_total           NUMERIC(14, 2)                        NOT NULL,
    valor_disponivel      NUMERIC(14, 2)                        NOT NULL,
    data_recebimento      DATE                                  NOT NULL,
    origem                VARCHAR(180)                                   DEFAULT NULL,
    criado_por_usuario_id UUID                                           DEFAULT NULL,
    criado_em             TIMESTAMP WITHOUT TIME ZONE           NOT NULL DEFAULT NOW(),
    atualizado_em         TIMESTAMP WITHOUT TIME ZONE           NOT NULL DEFAULT NOW(),
    excluido_em           TIMESTAMP WITHOUT TIME ZONE                    DEFAULT NULL,

    CONSTRAINT pk_receitas_campanha PRIMARY KEY (id),
    CONSTRAINT fk_receitas_campanha_candidato FOREIGN KEY (candidato_id)
        REFERENCES sige.lideres(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_receitas_campanha_criado_por FOREIGN KEY (criado_por_usuario_id)
        REFERENCES sige.usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT ck_receitas_campanha_valor_total CHECK (valor_total > 0),
    CONSTRAINT ck_receitas_campanha_valor_disponivel CHECK (
        valor_disponivel >= 0 AND valor_disponivel <= valor_total
    )
);

CREATE INDEX IF NOT EXISTS ix_receitas_campanha_candidato
    ON sige.receitas_campanha(candidato_id)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_receitas_campanha_tipo_recurso
    ON sige.receitas_campanha(tipo_recurso)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_receitas_campanha_data_recebimento
    ON sige.receitas_campanha(data_recebimento DESC)
    WHERE excluido_em IS NULL;

DROP TRIGGER IF EXISTS trg_receitas_campanha_atualizado_em ON sige.receitas_campanha;

CREATE TRIGGER trg_receitas_campanha_atualizado_em
    BEFORE UPDATE ON sige.receitas_campanha
    FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();

-- -------------------------------------------------------------
-- Tabela de categorias permitidas por tipo de recurso
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sige.categorias_permitidas_recurso (
    tipo_recurso  sige.tipo_recurso_campanha_enum  NOT NULL,
    categoria     VARCHAR(60)                       NOT NULL,
    ativo         BOOLEAN                           NOT NULL DEFAULT TRUE,
    criado_em     TIMESTAMP WITHOUT TIME ZONE       NOT NULL DEFAULT NOW(),
    atualizado_em TIMESTAMP WITHOUT TIME ZONE       NOT NULL DEFAULT NOW(),

    CONSTRAINT pk_categorias_permitidas_recurso PRIMARY KEY (tipo_recurso, categoria)
);

DROP TRIGGER IF EXISTS trg_categorias_permitidas_recurso_atualizado_em ON sige.categorias_permitidas_recurso;

CREATE TRIGGER trg_categorias_permitidas_recurso_atualizado_em
    BEFORE UPDATE ON sige.categorias_permitidas_recurso
    FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();

INSERT INTO sige.categorias_permitidas_recurso (tipo_recurso, categoria, ativo) VALUES
    ('fundo_partidario', 'marketing', TRUE),
    ('fundo_partidario', 'pessoal', TRUE),
    ('fundo_partidario', 'eventos', TRUE),
    ('fundo_partidario', 'juridico', TRUE),
    ('fundo_partidario', 'comunicacao', TRUE),
    ('fundo_partidario', 'material_grafico', TRUE),
    ('fundo_partidario', 'tecnologia', TRUE),

    ('fundo_eleitoral', 'combustivel', TRUE),
    ('fundo_eleitoral', 'marketing', TRUE),
    ('fundo_eleitoral', 'pessoal', TRUE),
    ('fundo_eleitoral', 'eventos', TRUE),
    ('fundo_eleitoral', 'deslocamento', TRUE),
    ('fundo_eleitoral', 'material_grafico', TRUE),
    ('fundo_eleitoral', 'comunicacao', TRUE),
    ('fundo_eleitoral', 'alimentacao', TRUE),

    ('doacao_privada', 'combustivel', TRUE),
    ('doacao_privada', 'marketing', TRUE),
    ('doacao_privada', 'pessoal', TRUE),
    ('doacao_privada', 'eventos', TRUE),
    ('doacao_privada', 'deslocamento', TRUE),
    ('doacao_privada', 'material_grafico', TRUE),
    ('doacao_privada', 'comunicacao', TRUE),
    ('doacao_privada', 'alimentacao', TRUE),
    ('doacao_privada', 'juridico', TRUE),
    ('doacao_privada', 'tecnologia', TRUE)
ON CONFLICT (tipo_recurso, categoria) DO NOTHING;

-- -------------------------------------------------------------
-- Tabela de despesas de campanha
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sige.despesas_campanha (
    id                        UUID                                   NOT NULL DEFAULT gen_random_uuid(),
    candidato_id              UUID                                   NOT NULL,
    receita_id                UUID                                   NOT NULL,
    fornecedor_id             UUID                                   NOT NULL,
    categoria                 VARCHAR(60)                            NOT NULL,
    valor                     NUMERIC(14, 2)                         NOT NULL,
    data_despesa              DATE                                   NOT NULL,
    descricao                 TEXT                                            DEFAULT NULL,
    classificacao_conformidade sige.classificacao_conformidade_enum  NOT NULL DEFAULT 'valida',
    conformidade_motivo       TEXT                                            DEFAULT NULL,
    desvio_padrao_percentual  NUMERIC(12, 2)                                  DEFAULT NULL,
    criado_por_usuario_id     UUID                                            DEFAULT NULL,
    criado_em                 TIMESTAMP WITHOUT TIME ZONE            NOT NULL DEFAULT NOW(),
    atualizado_em             TIMESTAMP WITHOUT TIME ZONE            NOT NULL DEFAULT NOW(),
    excluido_em               TIMESTAMP WITHOUT TIME ZONE                     DEFAULT NULL,

    CONSTRAINT pk_despesas_campanha PRIMARY KEY (id),
    CONSTRAINT fk_despesas_campanha_candidato FOREIGN KEY (candidato_id)
        REFERENCES sige.lideres(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_despesas_campanha_receita FOREIGN KEY (receita_id)
        REFERENCES sige.receitas_campanha(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_despesas_campanha_fornecedor FOREIGN KEY (fornecedor_id)
        REFERENCES sige.fornecedores_campanha(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_despesas_campanha_criado_por FOREIGN KEY (criado_por_usuario_id)
        REFERENCES sige.usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT ck_despesas_campanha_valor CHECK (valor > 0)
);

CREATE INDEX IF NOT EXISTS ix_despesas_campanha_candidato
    ON sige.despesas_campanha(candidato_id)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_despesas_campanha_receita
    ON sige.despesas_campanha(receita_id)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_despesas_campanha_categoria
    ON sige.despesas_campanha(categoria)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_despesas_campanha_data_despesa
    ON sige.despesas_campanha(data_despesa DESC)
    WHERE excluido_em IS NULL;

CREATE INDEX IF NOT EXISTS ix_despesas_campanha_conformidade
    ON sige.despesas_campanha(classificacao_conformidade)
    WHERE excluido_em IS NULL;

DROP TRIGGER IF EXISTS trg_despesas_campanha_atualizado_em ON sige.despesas_campanha;

CREATE TRIGGER trg_despesas_campanha_atualizado_em
    BEFORE UPDATE ON sige.despesas_campanha
    FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();

-- -------------------------------------------------------------
-- Views operacionais e de auditoria
-- -------------------------------------------------------------
DROP VIEW IF EXISTS sige.vw_financeiro_fornecedores CASCADE;
DROP VIEW IF EXISTS sige.vw_financeiro_receitas CASCADE;
DROP VIEW IF EXISTS sige.vw_financeiro_despesas CASCADE;
DROP VIEW IF EXISTS sige.vw_financeiro_rastreabilidade CASCADE;
DROP VIEW IF EXISTS sige.vw_financeiro_alertas CASCADE;

CREATE VIEW sige.vw_financeiro_fornecedores
    WITH (security_barrier = true)
AS
SELECT
    f.id,
    f.candidato_id,
    l.nome AS candidato_nome,
    f.nome,
    f.documento,
    f.tipo_fornecedor,
    f.criado_por_usuario_id,
    u.nome AS criado_por_usuario_nome,
    f.criado_em,
    f.atualizado_em
FROM sige.fornecedores_campanha f
INNER JOIN sige.lideres l
    ON l.id = f.candidato_id AND l.excluido_em IS NULL
LEFT JOIN sige.usuarios u
    ON u.id = f.criado_por_usuario_id AND u.excluido_em IS NULL
WHERE f.excluido_em IS NULL;

CREATE VIEW sige.vw_financeiro_receitas
    WITH (security_barrier = true)
AS
SELECT
    r.id,
    r.candidato_id,
    l.nome AS candidato_nome,
    r.tipo_recurso::text AS tipo_recurso,
    r.valor_total,
    r.valor_disponivel,
    (r.valor_total - r.valor_disponivel)::NUMERIC(14, 2) AS valor_utilizado,
    CASE
        WHEN r.valor_total > 0
            THEN ROUND((((r.valor_total - r.valor_disponivel) / r.valor_total) * 100)::NUMERIC, 2)::NUMERIC(6, 2)
        ELSE 0::NUMERIC(6, 2)
    END AS percentual_utilizado,
    r.data_recebimento,
    r.origem,
    r.criado_por_usuario_id,
    u.nome AS criado_por_usuario_nome,
    r.criado_em,
    r.atualizado_em
FROM sige.receitas_campanha r
INNER JOIN sige.lideres l
    ON l.id = r.candidato_id AND l.excluido_em IS NULL
LEFT JOIN sige.usuarios u
    ON u.id = r.criado_por_usuario_id AND u.excluido_em IS NULL
WHERE r.excluido_em IS NULL;

CREATE VIEW sige.vw_financeiro_despesas
    WITH (security_barrier = true)
AS
SELECT
    d.id,
    d.candidato_id,
    l.nome AS candidato_nome,
    d.receita_id,
    r.tipo_recurso::text AS tipo_recurso,
    r.origem AS receita_origem,
    d.fornecedor_id,
    f.nome AS fornecedor_nome,
    d.categoria,
    d.valor,
    d.data_despesa,
    d.descricao,
    d.classificacao_conformidade::text AS classificacao_conformidade,
    d.conformidade_motivo,
    d.desvio_padrao_percentual,
    d.criado_por_usuario_id,
    u.nome AS criado_por_usuario_nome,
    d.criado_em,
    d.atualizado_em
FROM sige.despesas_campanha d
INNER JOIN sige.lideres l
    ON l.id = d.candidato_id AND l.excluido_em IS NULL
INNER JOIN sige.receitas_campanha r
    ON r.id = d.receita_id AND r.excluido_em IS NULL
INNER JOIN sige.fornecedores_campanha f
    ON f.id = d.fornecedor_id AND f.excluido_em IS NULL
LEFT JOIN sige.usuarios u
    ON u.id = d.criado_por_usuario_id AND u.excluido_em IS NULL
WHERE d.excluido_em IS NULL;

CREATE VIEW sige.vw_financeiro_rastreabilidade AS
SELECT
    d.id AS despesa_id,
    d.data_despesa,
    d.candidato_id,
    d.candidato_nome,
    d.categoria,
    d.valor AS despesa_valor,
    d.classificacao_conformidade,
    d.conformidade_motivo,
    d.fornecedor_id,
    d.fornecedor_nome,
    d.receita_id,
    d.tipo_recurso,
    d.receita_origem,
    r.valor_total AS receita_valor_total,
    r.valor_disponivel AS receita_valor_disponivel_atual,
    r.data_recebimento AS receita_data_recebimento
FROM sige.vw_financeiro_despesas d
INNER JOIN sige.vw_financeiro_receitas r
    ON r.id = d.receita_id;

CREATE VIEW sige.vw_financeiro_alertas AS
WITH parametros AS (
    SELECT
        limite_percentual_categoria_excessiva,
        limite_percentual_saldo_critico,
        fator_despesa_fora_padrao,
        janela_media_despesa_dias
    FROM sige.financeiro_parametros
    WHERE id = 1
),
gasto_candidato AS (
    SELECT
        candidato_id,
        SUM(valor)::NUMERIC(14, 2) AS total_gasto
    FROM sige.despesas_campanha
    WHERE excluido_em IS NULL
    GROUP BY candidato_id
),
gasto_categoria AS (
    SELECT
        d.candidato_id,
        d.categoria,
        SUM(d.valor)::NUMERIC(14, 2) AS total_categoria,
        ROUND(((SUM(d.valor) / NULLIF(gc.total_gasto, 0)) * 100)::NUMERIC, 2)::NUMERIC(6, 2) AS percentual_categoria
    FROM sige.despesas_campanha d
    INNER JOIN gasto_candidato gc
        ON gc.candidato_id = d.candidato_id
    WHERE d.excluido_em IS NULL
    GROUP BY d.candidato_id, d.categoria, gc.total_gasto
),
media_categoria AS (
    SELECT
        candidato_id,
        categoria,
        AVG(valor)::NUMERIC(14, 2) AS media_valor,
        COUNT(*) AS total_lancamentos
    FROM sige.despesas_campanha
    WHERE excluido_em IS NULL
      AND data_despesa >= (NOW()::DATE - INTERVAL '180 days')
    GROUP BY candidato_id, categoria
),
outliers AS (
    SELECT
        d.id,
        d.candidato_id,
        d.categoria,
        d.valor,
        d.data_despesa,
        m.media_valor,
        m.total_lancamentos,
        ROUND((((d.valor / NULLIF(m.media_valor, 0)) - 1) * 100)::NUMERIC, 2)::NUMERIC(12, 2) AS desvio_percentual
    FROM sige.despesas_campanha d
    INNER JOIN media_categoria m
        ON m.candidato_id = d.candidato_id
       AND m.categoria = d.categoria
    CROSS JOIN parametros p
    WHERE d.excluido_em IS NULL
      AND d.data_despesa >= (NOW()::DATE - (p.janela_media_despesa_dias || ' days')::INTERVAL)
      AND m.total_lancamentos >= 3
      AND m.media_valor > 0
      AND d.valor >= (m.media_valor * p.fator_despesa_fora_padrao)
)
SELECT
    gen_random_uuid() AS id,
    gc.candidato_id,
    NULL::UUID AS receita_id,
    NULL::UUID AS despesa_id,
    'categoria_excessiva'::VARCHAR(40) AS alerta_codigo,
    'Uso excessivo por categoria'::VARCHAR(120) AS alerta_titulo,
    (
        'Categoria ' || gc.categoria || ' consumiu ' || gc.percentual_categoria::TEXT ||
        '% do gasto total do candidato.'
    )::TEXT AS alerta_descricao,
    'alto'::VARCHAR(20) AS alerta_nivel,
    gc.percentual_categoria::NUMERIC(12, 2) AS indicador_percentual,
    NOW()::TIMESTAMP WITHOUT TIME ZONE AS gerado_em
FROM gasto_categoria gc
CROSS JOIN parametros p
WHERE gc.percentual_categoria > p.limite_percentual_categoria_excessiva

UNION ALL

SELECT
    gen_random_uuid() AS id,
    r.candidato_id,
    r.id AS receita_id,
    NULL::UUID AS despesa_id,
    'saldo_critico'::VARCHAR(40) AS alerta_codigo,
    'Saldo critico em receita'::VARCHAR(120) AS alerta_titulo,
    (
        'Receita de ' || r.tipo_recurso::TEXT || ' com saldo de apenas ' ||
        ROUND(((r.valor_disponivel / NULLIF(r.valor_total, 0)) * 100)::NUMERIC, 2)::TEXT || '%.'
    )::TEXT AS alerta_descricao,
    'medio'::VARCHAR(20) AS alerta_nivel,
    ROUND(((r.valor_disponivel / NULLIF(r.valor_total, 0)) * 100)::NUMERIC, 2)::NUMERIC(12, 2) AS indicador_percentual,
    NOW()::TIMESTAMP WITHOUT TIME ZONE AS gerado_em
FROM sige.receitas_campanha r
CROSS JOIN parametros p
WHERE r.excluido_em IS NULL
  AND r.valor_total > 0
  AND ((r.valor_disponivel / r.valor_total) * 100) <= p.limite_percentual_saldo_critico

UNION ALL

SELECT
    gen_random_uuid() AS id,
    o.candidato_id,
    NULL::UUID AS receita_id,
    o.id AS despesa_id,
    'despesa_fora_padrao'::VARCHAR(40) AS alerta_codigo,
    'Despesa fora do padrao medio'::VARCHAR(120) AS alerta_titulo,
    (
        'Despesa de categoria ' || o.categoria || ' acima da media historica em ' ||
        o.desvio_percentual::TEXT || '%.'
    )::TEXT AS alerta_descricao,
    'alto'::VARCHAR(20) AS alerta_nivel,
    o.desvio_percentual::NUMERIC(12, 2) AS indicador_percentual,
    NOW()::TIMESTAMP WITHOUT TIME ZONE AS gerado_em
FROM outliers o;
