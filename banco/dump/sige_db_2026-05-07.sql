--
-- PostgreSQL database dump
--

\restrict fOuav4PRXDFQX6zHOD8jM4RGj9iEiGODqloAagA9FDUZPzVEzs6R4nLN1w5GpXJ

-- Dumped from database version 18.1
-- Dumped by pg_dump version 18.1

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: sige; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA sige;


--
-- Name: SCHEMA sige; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA sige IS 'Schema principal do SIGE - Sistema de Mapeamento Político';


--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: uuid-ossp; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA public;


--
-- Name: EXTENSION "uuid-ossp"; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION "uuid-ossp" IS 'generate universally unique identifiers (UUIDs)';


--
-- Name: perfil_usuario; Type: TYPE; Schema: sige; Owner: -
--

CREATE TYPE sige.perfil_usuario AS ENUM (
    'admin',
    'coordenador',
    'lider',
    'supervisor',
    'gestor'
);


--
-- Name: status_agenda_enum; Type: TYPE; Schema: sige; Owner: -
--

CREATE TYPE sige.status_agenda_enum AS ENUM (
    'pendente',
    'aprovado',
    'recusado'
);


--
-- Name: status_politico_enum; Type: TYPE; Schema: sige; Owner: -
--

CREATE TYPE sige.status_politico_enum AS ENUM (
    'apoiador',
    'indeciso',
    'oposicao'
);


--
-- Name: tipo_agenda_enum; Type: TYPE; Schema: sige; Owner: -
--

CREATE TYPE sige.tipo_agenda_enum AS ENUM (
    'visita',
    'reuniao',
    'outro'
);


--
-- Name: fn_atualizar_timestamp(); Type: FUNCTION; Schema: sige; Owner: -
--

CREATE FUNCTION sige.fn_atualizar_timestamp() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.atualizado_em = NOW();
    RETURN NEW;
END;
$$;


--
-- Name: FUNCTION fn_atualizar_timestamp(); Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON FUNCTION sige.fn_atualizar_timestamp() IS 'Atualiza automaticamente atualizado_em antes de qualquer UPDATE';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: agenda_eventos; Type: TABLE; Schema: sige; Owner: -
--

CREATE TABLE sige.agenda_eventos (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    lider_id uuid NOT NULL,
    criado_por_usuario_id uuid,
    titulo character varying(160) NOT NULL,
    tipo sige.tipo_agenda_enum DEFAULT 'reuniao'::sige.tipo_agenda_enum NOT NULL,
    descricao text,
    local_evento character varying(180) DEFAULT NULL::character varying,
    data_solicitada_inicio timestamp without time zone NOT NULL,
    data_solicitada_fim timestamp without time zone,
    data_confirmada_inicio timestamp without time zone,
    data_confirmada_fim timestamp without time zone,
    status sige.status_agenda_enum DEFAULT 'pendente'::sige.status_agenda_enum NOT NULL,
    observacoes_solicitacao text,
    observacoes_decisao text,
    decidido_por uuid,
    decidido_em timestamp without time zone,
    criado_em timestamp without time zone DEFAULT now() NOT NULL,
    atualizado_em timestamp without time zone DEFAULT now() NOT NULL,
    excluido_em timestamp without time zone,
    CONSTRAINT ck_agenda_eventos_intervalo_confirmado CHECK (((data_confirmada_inicio IS NULL) OR (data_confirmada_fim IS NULL) OR (data_confirmada_fim >= data_confirmada_inicio))),
    CONSTRAINT ck_agenda_eventos_intervalo_solicitado CHECK (((data_solicitada_fim IS NULL) OR (data_solicitada_fim >= data_solicitada_inicio))),
    CONSTRAINT ck_agenda_eventos_status_decisao CHECK ((((status = 'pendente'::sige.status_agenda_enum) AND (decidido_por IS NULL) AND (decidido_em IS NULL) AND (data_confirmada_inicio IS NULL) AND (data_confirmada_fim IS NULL)) OR ((status = 'aprovado'::sige.status_agenda_enum) AND (decidido_por IS NOT NULL) AND (decidido_em IS NOT NULL) AND (data_confirmada_inicio IS NOT NULL)) OR ((status = 'recusado'::sige.status_agenda_enum) AND (decidido_por IS NOT NULL) AND (decidido_em IS NOT NULL) AND (data_confirmada_inicio IS NULL) AND (data_confirmada_fim IS NULL))))
);


--
-- Name: TABLE agenda_eventos; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON TABLE sige.agenda_eventos IS 'SolicitaÃ§Ãµes de agenda criadas por lÃ­deres e decididas por gestor/admin';


--
-- Name: COLUMN agenda_eventos.criado_por_usuario_id; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.agenda_eventos.criado_por_usuario_id IS 'UsuÃ¡rio autenticado que abriu a solicitaÃ§Ã£o quando nÃ£o vier diretamente do login do lÃ­der';


--
-- Name: COLUMN agenda_eventos.decidido_por; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.agenda_eventos.decidido_por IS 'Gestor/admin que aprovou ou recusou a solicitaÃ§Ã£o';


--
-- Name: apoiadores; Type: TABLE; Schema: sige; Owner: -
--

CREATE TABLE sige.apoiadores (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    lider_id uuid NOT NULL,
    nome character varying(150) NOT NULL,
    cpf text NOT NULL,
    cpf_hash character varying(64) NOT NULL,
    telefone character varying(20) DEFAULT NULL::character varying,
    bairro character varying(100) DEFAULT NULL::character varying,
    status_politico sige.status_politico_enum DEFAULT 'indeciso'::sige.status_politico_enum NOT NULL,
    observacoes text,
    criado_por uuid,
    criado_em timestamp without time zone DEFAULT now() NOT NULL,
    atualizado_em timestamp without time zone DEFAULT now() NOT NULL,
    excluido_em timestamp without time zone
);


--
-- Name: TABLE apoiadores; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON TABLE sige.apoiadores IS 'Pessoas vinculadas politicamente a um líder';


--
-- Name: COLUMN apoiadores.id; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.apoiadores.id IS 'UUID gerado automaticamente - evita enumeração de IDs';


--
-- Name: COLUMN apoiadores.lider_id; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.apoiadores.lider_id IS 'FK obrigatória: todo apoiador pertence a exatamente um líder';


--
-- Name: COLUMN apoiadores.cpf; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.apoiadores.cpf IS 'CPF criptografado AES-256 pela aplicação - LGPD dado pessoal Art. 5';


--
-- Name: COLUMN apoiadores.cpf_hash; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.apoiadores.cpf_hash IS 'HMAC-SHA256 do CPF para verificação de unicidade - não reversível';


--
-- Name: COLUMN apoiadores.status_politico; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.apoiadores.status_politico IS 'Dado sensível conforme LGPD Art. 11 - base legal: legítimo interesse de campanha';


--
-- Name: COLUMN apoiadores.criado_por; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.apoiadores.criado_por IS 'FK para usuários: rastreabilidade de quem cadastrou o apoiador';


--
-- Name: COLUMN apoiadores.excluido_em; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.apoiadores.excluido_em IS 'Soft delete - LGPD: direito ao esquecimento / portabilidade';


--
-- Name: lideres; Type: TABLE; Schema: sige; Owner: -
--

CREATE TABLE sige.lideres (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    nome character varying(150) NOT NULL,
    cpf text NOT NULL,
    cpf_hash character varying(64) NOT NULL,
    telefone character varying(20) DEFAULT NULL::character varying,
    bairro character varying(100) DEFAULT NULL::character varying,
    votos_estimados integer DEFAULT 0 NOT NULL,
    observacoes text,
    status boolean DEFAULT true NOT NULL,
    criado_por uuid,
    criado_em timestamp without time zone DEFAULT now() NOT NULL,
    atualizado_em timestamp without time zone DEFAULT now() NOT NULL,
    excluido_em timestamp without time zone,
    senha character varying(255) DEFAULT NULL::character varying,
    CONSTRAINT ck_lideres_votos CHECK ((votos_estimados >= 0))
);


--
-- Name: TABLE lideres; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON TABLE sige.lideres IS 'Lideranças políticas cadastradas no sistema';


--
-- Name: COLUMN lideres.id; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.lideres.id IS 'UUID gerado automaticamente - evita enumeração de IDs';


--
-- Name: COLUMN lideres.cpf; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.lideres.cpf IS 'CPF criptografado AES-256 pela aplicação - LGPD dado pessoal Art. 5';


--
-- Name: COLUMN lideres.cpf_hash; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.lideres.cpf_hash IS 'HMAC-SHA256 do CPF para verificação de unicidade - não reversível';


--
-- Name: COLUMN lideres.votos_estimados; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.lideres.votos_estimados IS 'Estimativa declarada de votos sob influência do líder';


--
-- Name: COLUMN lideres.criado_por; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.lideres.criado_por IS 'FK para usuários: rastreabilidade de quem cadastrou o líder';


--
-- Name: COLUMN lideres.excluido_em; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.lideres.excluido_em IS 'Soft delete - LGPD: direito ao esquecimento / portabilidade';


--
-- Name: COLUMN lideres.senha; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.lideres.senha IS 'Hash bcrypt â€” NULL significa lÃ­der sem acesso ao sistema';


--
-- Name: logs_auditoria; Type: TABLE; Schema: sige; Owner: -
--

CREATE TABLE sige.logs_auditoria (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid,
    tabela character varying(100) NOT NULL,
    operacao character varying(10) NOT NULL,
    registro_id uuid,
    dados_anteriores jsonb,
    dados_novos jsonb,
    ip_origem character varying(45) DEFAULT NULL::character varying,
    criado_em timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT ck_logs_operacao CHECK (((operacao)::text = ANY ((ARRAY['INSERT'::character varying, 'UPDATE'::character varying, 'DELETE'::character varying, 'LOGIN'::character varying, 'LOGOUT'::character varying, 'ACCESS'::character varying])::text[])))
);


--
-- Name: TABLE logs_auditoria; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON TABLE sige.logs_auditoria IS 'Trilha de auditoria completa - LGPD rastreabilidade e conformidade';


--
-- Name: COLUMN logs_auditoria.operacao; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.logs_auditoria.operacao IS 'Tipo de operação: INSERT, UPDATE, DELETE, LOGIN, LOGOUT, ACCESS';


--
-- Name: COLUMN logs_auditoria.dados_anteriores; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.logs_auditoria.dados_anteriores IS 'Estado anterior do registro em JSON - para rollback e auditoria';


--
-- Name: COLUMN logs_auditoria.dados_novos; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.logs_auditoria.dados_novos IS 'Estado novo do registro em JSON - para auditoria';


--
-- Name: tokens_refresh; Type: TABLE; Schema: sige; Owner: -
--

CREATE TABLE sige.tokens_refresh (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    token_hash character varying(64) NOT NULL,
    ip_origem character varying(45) DEFAULT NULL::character varying,
    user_agent text,
    expira_em timestamp without time zone NOT NULL,
    revogado boolean DEFAULT false NOT NULL,
    criado_em timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: TABLE tokens_refresh; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON TABLE sige.tokens_refresh IS 'Refresh tokens para autenticação JWT - scaffolded para expansão futura';


--
-- Name: COLUMN tokens_refresh.token_hash; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.tokens_refresh.token_hash IS 'SHA-256 do token bruto - token jamais armazenado em texto puro';


--
-- Name: COLUMN tokens_refresh.ip_origem; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.tokens_refresh.ip_origem IS 'IP de origem para auditoria e detecção de anomalias';


--
-- Name: usuarios; Type: TABLE; Schema: sige; Owner: -
--

CREATE TABLE sige.usuarios (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    nome character varying(150) NOT NULL,
    email character varying(150) NOT NULL,
    senha character varying(255) NOT NULL,
    perfil sige.perfil_usuario DEFAULT 'admin'::sige.perfil_usuario NOT NULL,
    status boolean DEFAULT true NOT NULL,
    criado_em timestamp without time zone DEFAULT now() NOT NULL,
    atualizado_em timestamp without time zone DEFAULT now() NOT NULL,
    excluido_em timestamp without time zone,
    cpf text,
    cpf_hash character varying(64) DEFAULT NULL::character varying
);


--
-- Name: TABLE usuarios; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON TABLE sige.usuarios IS 'Usuários autenticados do sistema';


--
-- Name: COLUMN usuarios.id; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.usuarios.id IS 'UUID gerado automaticamente - evita enumeração de IDs';


--
-- Name: COLUMN usuarios.senha; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.usuarios.senha IS 'Hash argon2id/bcrypt - NUNCA armazenar senha em texto puro';


--
-- Name: COLUMN usuarios.perfil; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.usuarios.perfil IS 'Perfil de acesso: admin, coordenador, lider, supervisor';


--
-- Name: COLUMN usuarios.excluido_em; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.usuarios.excluido_em IS 'Soft delete - LGPD: direito ao esquecimento / portabilidade';


--
-- Name: COLUMN usuarios.cpf; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.usuarios.cpf IS 'CPF criptografado AES-256 pela aplicaÃ§Ã£o - LGPD';


--
-- Name: COLUMN usuarios.cpf_hash; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON COLUMN sige.usuarios.cpf_hash IS 'HMAC-SHA256 do CPF â€” exclusividade de login';


--
-- Name: vw_agenda_eventos; Type: VIEW; Schema: sige; Owner: -
--

CREATE VIEW sige.vw_agenda_eventos WITH (security_barrier='true') AS
 SELECT a.id,
    a.titulo,
    (a.tipo)::text AS tipo,
    a.descricao,
    a.local_evento,
    a.data_solicitada_inicio,
    a.data_solicitada_fim,
    a.data_confirmada_inicio,
    a.data_confirmada_fim,
    (a.status)::text AS status,
    a.observacoes_solicitacao,
    a.observacoes_decisao,
    a.decidido_por,
    a.decidido_em,
    a.criado_por_usuario_id,
    a.criado_em,
    a.atualizado_em,
    l.id AS lider_id,
    l.nome AS lider_nome,
    l.bairro AS lider_bairro,
    uc.nome AS criado_por_usuario_nome,
    ud.nome AS decidido_por_nome
   FROM (((sige.agenda_eventos a
     JOIN sige.lideres l ON (((l.id = a.lider_id) AND (l.excluido_em IS NULL))))
     LEFT JOIN sige.usuarios uc ON (((uc.id = a.criado_por_usuario_id) AND (uc.excluido_em IS NULL))))
     LEFT JOIN sige.usuarios ud ON (((ud.id = a.decidido_por) AND (ud.excluido_em IS NULL))))
  WHERE (a.excluido_em IS NULL);


--
-- Name: VIEW vw_agenda_eventos; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON VIEW sige.vw_agenda_eventos IS 'Eventos de agenda com lÃ­der solicitante e gestor/admin decisor';


--
-- Name: vw_apoiadores_ativos; Type: VIEW; Schema: sige; Owner: -
--

CREATE VIEW sige.vw_apoiadores_ativos WITH (security_barrier='true') AS
 SELECT a.id,
    a.nome,
    a.cpf_hash,
    a.telefone,
    a.bairro,
    a.status_politico,
    a.observacoes,
    a.criado_em,
    a.atualizado_em,
    l.id AS lider_id,
    l.nome AS lider_nome,
    l.bairro AS lider_bairro
   FROM (sige.apoiadores a
     JOIN sige.lideres l ON (((l.id = a.lider_id) AND (l.excluido_em IS NULL))))
  WHERE (a.excluido_em IS NULL);


--
-- Name: VIEW vw_apoiadores_ativos; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON VIEW sige.vw_apoiadores_ativos IS 'Apoiadores ativos com dados do líder vinculado — soft delete aplicado em ambas as tabelas';


--
-- Name: vw_credenciais; Type: VIEW; Schema: sige; Owner: -
--

CREATE VIEW sige.vw_credenciais WITH (security_barrier='true') AS
 SELECT u.id,
    u.nome,
    u.cpf_hash,
    u.senha AS senha_hash,
    (u.perfil)::text AS perfil,
    'usuario'::text AS tipo
   FROM sige.usuarios u
  WHERE ((u.excluido_em IS NULL) AND (u.status = true) AND (u.cpf_hash IS NOT NULL) AND (u.senha IS NOT NULL))
UNION ALL
 SELECT l.id,
    l.nome,
    l.cpf_hash,
    l.senha AS senha_hash,
    'lider'::text AS perfil,
    'lider'::text AS tipo
   FROM sige.lideres l
  WHERE ((l.excluido_em IS NULL) AND (l.status = true) AND (l.senha IS NOT NULL));


--
-- Name: VIEW vw_credenciais; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON VIEW sige.vw_credenciais IS 'View unificada de credenciais para autenticaÃ§Ã£o via CPF';


--
-- Name: vw_lideres_ativos; Type: VIEW; Schema: sige; Owner: -
--

CREATE VIEW sige.vw_lideres_ativos WITH (security_barrier='true') AS
 SELECT l.id,
    l.nome,
    l.cpf_hash,
    l.telefone,
    l.bairro,
    l.votos_estimados,
    l.observacoes,
    l.status,
    l.criado_em,
    l.atualizado_em,
    u.id AS criado_por_id,
    u.nome AS criado_por_nome
   FROM (sige.lideres l
     LEFT JOIN sige.usuarios u ON (((u.id = l.criado_por) AND (u.excluido_em IS NULL))))
  WHERE (l.excluido_em IS NULL);


--
-- Name: VIEW vw_lideres_ativos; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON VIEW sige.vw_lideres_ativos IS 'Líderes ativos com nome do usuário cadastrante — soft delete aplicado';


--
-- Name: vw_relatorio_consolidado; Type: VIEW; Schema: sige; Owner: -
--

CREATE VIEW sige.vw_relatorio_consolidado AS
 SELECT l.id AS lider_id,
    l.nome AS lider_nome,
    l.bairro AS lider_bairro,
    l.votos_estimados,
    l.status AS lider_ativo,
    l.criado_em AS lider_cadastrado_em,
    count(a.id) AS total_vinculados,
    count(a.id) FILTER (WHERE (a.status_politico = 'apoiador'::sige.status_politico_enum)) AS apoiadores,
    count(a.id) FILTER (WHERE (a.status_politico = 'indeciso'::sige.status_politico_enum)) AS indecisos,
    count(a.id) FILTER (WHERE (a.status_politico = 'oposicao'::sige.status_politico_enum)) AS oposicao,
    (l.votos_estimados + count(a.id) FILTER (WHERE (a.status_politico = 'apoiador'::sige.status_politico_enum))) AS potencial_total_votos,
    dense_rank() OVER (ORDER BY (l.votos_estimados + count(a.id) FILTER (WHERE (a.status_politico = 'apoiador'::sige.status_politico_enum))) DESC) AS posicao_ranking
   FROM (sige.lideres l
     LEFT JOIN sige.apoiadores a ON (((a.lider_id = l.id) AND (a.excluido_em IS NULL))))
  WHERE (l.excluido_em IS NULL)
  GROUP BY l.id, l.nome, l.bairro, l.votos_estimados, l.status, l.criado_em;


--
-- Name: VIEW vw_relatorio_consolidado; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON VIEW sige.vw_relatorio_consolidado IS 'Relatório político consolidado por líder com potencial total de votos e ranking final';


--
-- Name: vw_relatorio_por_bairro; Type: VIEW; Schema: sige; Owner: -
--

CREATE VIEW sige.vw_relatorio_por_bairro AS
 SELECT COALESCE(lideres.bairro, '(sem bairro)'::character varying) AS bairro,
    'lider'::text AS tipo,
    count(*) AS total,
    COALESCE(sum(lideres.votos_estimados), (0)::bigint) AS votos_estimados,
    NULL::bigint AS qtd_apoiadores,
    NULL::bigint AS qtd_indecisos,
    NULL::bigint AS qtd_oposicao
   FROM sige.lideres
  WHERE (lideres.excluido_em IS NULL)
  GROUP BY lideres.bairro
UNION ALL
 SELECT COALESCE(apoiadores.bairro, '(sem bairro)'::character varying) AS bairro,
    'apoiador'::text AS tipo,
    count(*) AS total,
    0 AS votos_estimados,
    count(*) FILTER (WHERE (apoiadores.status_politico = 'apoiador'::sige.status_politico_enum)) AS qtd_apoiadores,
    count(*) FILTER (WHERE (apoiadores.status_politico = 'indeciso'::sige.status_politico_enum)) AS qtd_indecisos,
    count(*) FILTER (WHERE (apoiadores.status_politico = 'oposicao'::sige.status_politico_enum)) AS qtd_oposicao
   FROM sige.apoiadores
  WHERE (apoiadores.excluido_em IS NULL)
  GROUP BY apoiadores.bairro;


--
-- Name: VIEW vw_relatorio_por_bairro; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON VIEW sige.vw_relatorio_por_bairro IS 'Consolidação por bairro/região de líderes e apoiadores — preparado para mapas e heatmaps';


--
-- Name: vw_relatorio_por_lider; Type: VIEW; Schema: sige; Owner: -
--

CREATE VIEW sige.vw_relatorio_por_lider AS
 SELECT l.id AS lider_id,
    l.nome AS lider_nome,
    l.bairro AS lider_bairro,
    l.votos_estimados,
    count(a.id) AS total_apoiadores,
    count(a.id) FILTER (WHERE (a.status_politico = 'apoiador'::sige.status_politico_enum)) AS qtd_apoiadores,
    count(a.id) FILTER (WHERE (a.status_politico = 'indeciso'::sige.status_politico_enum)) AS qtd_indecisos,
    count(a.id) FILTER (WHERE (a.status_politico = 'oposicao'::sige.status_politico_enum)) AS qtd_oposicao,
        CASE
            WHEN (count(a.id) > 0) THEN round((((count(a.id) FILTER (WHERE (a.status_politico = 'apoiador'::sige.status_politico_enum)))::numeric * 100.0) / (count(a.id))::numeric), 1)
            ELSE (0)::numeric
        END AS perc_apoiadores,
        CASE
            WHEN (count(a.id) > 0) THEN round((((count(a.id) FILTER (WHERE (a.status_politico = 'indeciso'::sige.status_politico_enum)))::numeric * 100.0) / (count(a.id))::numeric), 1)
            ELSE (0)::numeric
        END AS perc_indecisos,
        CASE
            WHEN (count(a.id) > 0) THEN round((((count(a.id) FILTER (WHERE (a.status_politico = 'oposicao'::sige.status_politico_enum)))::numeric * 100.0) / (count(a.id))::numeric), 1)
            ELSE (0)::numeric
        END AS perc_oposicao,
    dense_rank() OVER (ORDER BY l.votos_estimados DESC) AS ranking_votos,
    dense_rank() OVER (ORDER BY (count(a.id)) DESC) AS ranking_apoiadores
   FROM (sige.lideres l
     LEFT JOIN sige.apoiadores a ON (((a.lider_id = l.id) AND (a.excluido_em IS NULL))))
  WHERE (l.excluido_em IS NULL)
  GROUP BY l.id, l.nome, l.bairro, l.votos_estimados;


--
-- Name: VIEW vw_relatorio_por_lider; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON VIEW sige.vw_relatorio_por_lider IS 'Breakdown por líder com totais, percentuais e ranking — para relatório e dashboard';


--
-- Name: vw_relatorio_resumo; Type: VIEW; Schema: sige; Owner: -
--

CREATE VIEW sige.vw_relatorio_resumo AS
 SELECT ( SELECT count(*) AS count
           FROM sige.lideres
          WHERE (lideres.excluido_em IS NULL)) AS total_lideres,
    ( SELECT count(*) AS count
           FROM sige.apoiadores
          WHERE (apoiadores.excluido_em IS NULL)) AS total_apoiadores,
    ( SELECT COALESCE(sum(lideres.votos_estimados), (0)::bigint) AS "coalesce"
           FROM sige.lideres
          WHERE (lideres.excluido_em IS NULL)) AS total_votos_estimados,
    ( SELECT COALESCE(round(avg(lideres.votos_estimados), 2), (0)::numeric) AS "coalesce"
           FROM sige.lideres
          WHERE (lideres.excluido_em IS NULL)) AS media_votos_por_lider,
    ( SELECT count(*) AS count
           FROM sige.apoiadores
          WHERE ((apoiadores.excluido_em IS NULL) AND (apoiadores.status_politico = 'apoiador'::sige.status_politico_enum))) AS total_apoiadores_confirmados,
    ( SELECT count(*) AS count
           FROM sige.apoiadores
          WHERE ((apoiadores.excluido_em IS NULL) AND (apoiadores.status_politico = 'indeciso'::sige.status_politico_enum))) AS total_indecisos,
    ( SELECT count(*) AS count
           FROM sige.apoiadores
          WHERE ((apoiadores.excluido_em IS NULL) AND (apoiadores.status_politico = 'oposicao'::sige.status_politico_enum))) AS total_oposicao,
    now() AS gerado_em;


--
-- Name: VIEW vw_relatorio_resumo; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON VIEW sige.vw_relatorio_resumo IS 'Resumo geral do sistema: totais e médias para o painel principal';


--
-- Name: vw_usuarios_ativos; Type: VIEW; Schema: sige; Owner: -
--

CREATE VIEW sige.vw_usuarios_ativos WITH (security_barrier='true') AS
 SELECT id,
    nome,
    email,
    perfil,
    status,
    criado_em,
    atualizado_em
   FROM sige.usuarios
  WHERE (excluido_em IS NULL);


--
-- Name: VIEW vw_usuarios_ativos; Type: COMMENT; Schema: sige; Owner: -
--

COMMENT ON VIEW sige.vw_usuarios_ativos IS 'Usuários ativos — sem coluna senha, aplica soft delete automaticamente';


--
-- Data for Name: agenda_eventos; Type: TABLE DATA; Schema: sige; Owner: -
--

COPY sige.agenda_eventos (id, lider_id, criado_por_usuario_id, titulo, tipo, descricao, local_evento, data_solicitada_inicio, data_solicitada_fim, data_confirmada_inicio, data_confirmada_fim, status, observacoes_solicitacao, observacoes_decisao, decidido_por, decidido_em, criado_em, atualizado_em, excluido_em) FROM stdin;
454430eb-ab47-40db-be71-89468b0d6475	459f18d4-0e3b-4687-bffb-47cedb7f12d7	83637460-9227-4c0a-bf61-ecdb66ca795f	Reuniao com liderancas de bairro	reuniao	\N	Gabinete regional	2026-05-10 14:00:00	2026-05-10 15:00:00	2026-05-10 14:30:00	2026-05-10 15:30:00	aprovado	Solicitacao aberta para validar disponibilidade do deputado.	Agenda confirmada com ajuste de meia hora.	83637460-9227-4c0a-bf61-ecdb66ca795f	2026-05-08 02:03:42	2026-05-07 22:03:42.236621	2026-05-07 22:03:42.291766	\N
\.


--
-- Data for Name: apoiadores; Type: TABLE DATA; Schema: sige; Owner: -
--

COPY sige.apoiadores (id, lider_id, nome, cpf, cpf_hash, telefone, bairro, status_politico, observacoes, criado_por, criado_em, atualizado_em, excluido_em) FROM stdin;
8973a34a-c1a0-4050-b297-02f142f37945	459f18d4-0e3b-4687-bffb-47cedb7f12d7	JORGE HENRIQUE SILVA GUIMARAES	oARsLbtnvKJksCc0arSlyodt7Niz7B0+yU6LcjtGu8g=	4e586dfb5a64b0dce1fa5e8053c47a3e2e8d0a84fb0579233d86847950fc3912	(95) 98101-2720	Centenário	apoiador		\N	2026-05-07 21:13:26.642466	2026-05-07 22:24:12.550379	2026-05-07 22:24:12.550379
665a2328-f64a-4884-bfa6-19e3f378abb7	be761537-000d-45a3-b3a8-68137e5f6503	Beatriz Mendes	XLcO7nzNxSfwLSw/kO6tSD0zufqPpk9Ag65OD/XC3/k=	14a582af8f8ee94299d47a2f8bc2f24fa916687efa782cc0ef7a28b97ab5ccd7	(95) 98101-2720	Centenário	apoiador		\N	2026-05-07 22:25:25.683753	2026-05-07 22:25:25.683753	\N
6bb8094a-8af8-4617-896b-98f656116082	459f18d4-0e3b-4687-bffb-47cedb7f12d7	Beatriz Mendes	0KVZG2OiOpLfE1Te1XtxyayXw1U01lyqtYtLrL+wP3o=	2f11938bc8446b4ea4bcc5bd9dc5ba0c8583a0bd11f2f501eb2a86095e07048d	95981039220	Centenário	apoiador		\N	2026-05-07 22:25:37.356209	2026-05-07 22:25:37.356209	\N
9084a13c-60ae-45da-8cc2-31e437607d08	459f18d4-0e3b-4687-bffb-47cedb7f12d7	Jessy	7vYO8uGSWrrLd8D0n09PDnPG0on/q37esSMX78SW9xA=	3a4ff345b6ece4171ab5d488e911a8a04c7b6c851a69d193ba628b1d567607e6	(95) 9810-1272	Centenário	apoiador		\N	2026-05-07 22:26:02.538646	2026-05-07 22:26:02.538646	\N
c77fff0d-a5e7-45fb-9650-4f125595e37e	be761537-000d-45a3-b3a8-68137e5f6503	bianca	WS9oh72aLyb9xm4dfPtA8OsYrjUYs5i8LIc3m+nxbTM=	36ab60a731237dbb5f5d2308d13b8e669df13ef4ee466556fd4de816efbb05f7	(95) 98101-2720	Centenário	apoiador		\N	2026-05-07 22:26:29.451501	2026-05-07 22:26:29.451501	\N
92d48103-53aa-47ff-ac96-ef87bc74319e	459f18d4-0e3b-4687-bffb-47cedb7f12d7	JOÃO MATHEUS	PMizWSfxsLL9zTMMqUkIpyENERckM3v+JgNadRtR46s=	c4d827060a46e0eaee0e96f5a2832ee72ec2c31e164b682bf10bc6cae653773b	(95) 98101-2720	PRICUMÃ	apoiador		\N	2026-05-07 22:26:56.908114	2026-05-07 22:26:56.908114	\N
\.


--
-- Data for Name: lideres; Type: TABLE DATA; Schema: sige; Owner: -
--

COPY sige.lideres (id, nome, cpf, cpf_hash, telefone, bairro, votos_estimados, observacoes, status, criado_por, criado_em, atualizado_em, excluido_em, senha) FROM stdin;
459f18d4-0e3b-4687-bffb-47cedb7f12d7	Matheus barbosa bispo	qH7xDawPW2Ha3dp+YE8ThGY+z1LCxrqLzAxBcI/pUQo=	4e586dfb5a64b0dce1fa5e8053c47a3e2e8d0a84fb0579233d86847950fc3912	(95) 9810-12721	Centenário	50		t	\N	2026-05-07 20:55:24.428062	2026-05-07 20:55:24.428062	\N	\N
be761537-000d-45a3-b3a8-68137e5f6503	THAYNAR BARBOSA BISPO	YxFCjYGMh8u4l+C99mNGsprETJsQO6w+cmz91BhW2as=	14a582af8f8ee94299d47a2f8bc2f24fa916687efa782cc0ef7a28b97ab5ccd7	95981039220	CINTURÃO VERDE	70		t	\N	2026-05-07 22:25:09.606338	2026-05-07 22:25:09.606338	\N	\N
\.


--
-- Data for Name: logs_auditoria; Type: TABLE DATA; Schema: sige; Owner: -
--

COPY sige.logs_auditoria (id, usuario_id, tabela, operacao, registro_id, dados_anteriores, dados_novos, ip_origem, criado_em) FROM stdin;
\.


--
-- Data for Name: tokens_refresh; Type: TABLE DATA; Schema: sige; Owner: -
--

COPY sige.tokens_refresh (id, usuario_id, token_hash, ip_origem, user_agent, expira_em, revogado, criado_em) FROM stdin;
\.


--
-- Data for Name: usuarios; Type: TABLE DATA; Schema: sige; Owner: -
--

COPY sige.usuarios (id, nome, email, senha, perfil, status, criado_em, atualizado_em, excluido_em, cpf, cpf_hash) FROM stdin;
c9c2358a-41d2-4227-840c-389c8f3b911b	Administrador	admin@sige.local	$2y$12$S.yPl8HhNtC4lUH8t9vZsek/yQlLiVuNy/zNZ.ym3gGToC4g4prXW	admin	t	2026-05-07 19:28:17.963712	2026-05-07 19:58:48.860382	\N	FzMKp9rQQcrai/FbyRfbVAuIMxbWU4WTHbZWQi1wPnY=	4e586dfb5a64b0dce1fa5e8053c47a3e2e8d0a84fb0579233d86847950fc3912
83637460-9227-4c0a-bf61-ecdb66ca795f	Gestor de Agenda	gestor@sige.local	$2y$12$heVlp.MtCY9qCkLwZJ283esA4c2OltRPwttFzSK8hgbOG0Bsx80Re	gestor	t	2026-05-07 22:02:07.167363	2026-05-07 22:02:07.167363	\N	E8AeOmv0/+51aYwXaEDcq4khUj237nG7o7NbqElfQOs=	002e14aefbfa72856e2a1db246247ca23b4ea76cde5109c1b362271dbe8a8e4d
\.


--
-- Name: agenda_eventos pk_agenda_eventos; Type: CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.agenda_eventos
    ADD CONSTRAINT pk_agenda_eventos PRIMARY KEY (id);


--
-- Name: apoiadores pk_apoiadores; Type: CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.apoiadores
    ADD CONSTRAINT pk_apoiadores PRIMARY KEY (id);


--
-- Name: lideres pk_lideres; Type: CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.lideres
    ADD CONSTRAINT pk_lideres PRIMARY KEY (id);


--
-- Name: logs_auditoria pk_logs_auditoria; Type: CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.logs_auditoria
    ADD CONSTRAINT pk_logs_auditoria PRIMARY KEY (id);


--
-- Name: tokens_refresh pk_tokens_refresh; Type: CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.tokens_refresh
    ADD CONSTRAINT pk_tokens_refresh PRIMARY KEY (id);


--
-- Name: usuarios pk_usuarios; Type: CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.usuarios
    ADD CONSTRAINT pk_usuarios PRIMARY KEY (id);


--
-- Name: apoiadores uq_apoiadores_cpf_hash; Type: CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.apoiadores
    ADD CONSTRAINT uq_apoiadores_cpf_hash UNIQUE (cpf_hash);


--
-- Name: lideres uq_lideres_cpf_hash; Type: CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.lideres
    ADD CONSTRAINT uq_lideres_cpf_hash UNIQUE (cpf_hash);


--
-- Name: tokens_refresh uq_tokens_hash; Type: CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.tokens_refresh
    ADD CONSTRAINT uq_tokens_hash UNIQUE (token_hash);


--
-- Name: usuarios uq_usuarios_email; Type: CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.usuarios
    ADD CONSTRAINT uq_usuarios_email UNIQUE (email);


--
-- Name: ix_agenda_eventos_data_solicitada_inicio; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_agenda_eventos_data_solicitada_inicio ON sige.agenda_eventos USING btree (data_solicitada_inicio) WHERE (excluido_em IS NULL);


--
-- Name: ix_agenda_eventos_decidido_por; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_agenda_eventos_decidido_por ON sige.agenda_eventos USING btree (decidido_por) WHERE (excluido_em IS NULL);


--
-- Name: ix_agenda_eventos_lider_id; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_agenda_eventos_lider_id ON sige.agenda_eventos USING btree (lider_id) WHERE (excluido_em IS NULL);


--
-- Name: ix_agenda_eventos_status; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_agenda_eventos_status ON sige.agenda_eventos USING btree (status) WHERE (excluido_em IS NULL);


--
-- Name: ix_apoiadores_bairro; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_apoiadores_bairro ON sige.apoiadores USING btree (bairro) WHERE (excluido_em IS NULL);


--
-- Name: ix_apoiadores_lider_id; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_apoiadores_lider_id ON sige.apoiadores USING btree (lider_id) WHERE (excluido_em IS NULL);


--
-- Name: ix_apoiadores_lider_status_politico; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_apoiadores_lider_status_politico ON sige.apoiadores USING btree (lider_id, status_politico) WHERE (excluido_em IS NULL);


--
-- Name: ix_apoiadores_status_politico; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_apoiadores_status_politico ON sige.apoiadores USING btree (status_politico) WHERE (excluido_em IS NULL);


--
-- Name: ix_lideres_bairro; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_lideres_bairro ON sige.lideres USING btree (bairro) WHERE (excluido_em IS NULL);


--
-- Name: ix_lideres_criado_por; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_lideres_criado_por ON sige.lideres USING btree (criado_por);


--
-- Name: ix_lideres_status; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_lideres_status ON sige.lideres USING btree (status) WHERE (excluido_em IS NULL);


--
-- Name: ix_lideres_votos_estimados_rank; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_lideres_votos_estimados_rank ON sige.lideres USING btree (votos_estimados DESC) WHERE (excluido_em IS NULL);


--
-- Name: ix_logs_criado_em_desc; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_logs_criado_em_desc ON sige.logs_auditoria USING btree (criado_em DESC);


--
-- Name: ix_logs_registro_id; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_logs_registro_id ON sige.logs_auditoria USING btree (registro_id) WHERE (registro_id IS NOT NULL);


--
-- Name: ix_logs_tabela_operacao; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_logs_tabela_operacao ON sige.logs_auditoria USING btree (tabela, operacao);


--
-- Name: ix_logs_usuario_id; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_logs_usuario_id ON sige.logs_auditoria USING btree (usuario_id);


--
-- Name: ix_tokens_expira_em_ativos; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_tokens_expira_em_ativos ON sige.tokens_refresh USING btree (expira_em) WHERE (revogado = false);


--
-- Name: ix_tokens_usuario_id; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_tokens_usuario_id ON sige.tokens_refresh USING btree (usuario_id);


--
-- Name: ix_usuarios_perfil; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_usuarios_perfil ON sige.usuarios USING btree (perfil) WHERE (excluido_em IS NULL);


--
-- Name: ix_usuarios_status; Type: INDEX; Schema: sige; Owner: -
--

CREATE INDEX ix_usuarios_status ON sige.usuarios USING btree (status) WHERE (excluido_em IS NULL);


--
-- Name: uix_apoiadores_cpf_hash_ativo; Type: INDEX; Schema: sige; Owner: -
--

CREATE UNIQUE INDEX uix_apoiadores_cpf_hash_ativo ON sige.apoiadores USING btree (cpf_hash) WHERE (excluido_em IS NULL);


--
-- Name: uix_lideres_cpf_hash_ativo; Type: INDEX; Schema: sige; Owner: -
--

CREATE UNIQUE INDEX uix_lideres_cpf_hash_ativo ON sige.lideres USING btree (cpf_hash) WHERE (excluido_em IS NULL);


--
-- Name: uix_usuarios_email_ativo; Type: INDEX; Schema: sige; Owner: -
--

CREATE UNIQUE INDEX uix_usuarios_email_ativo ON sige.usuarios USING btree (email) WHERE (excluido_em IS NULL);


--
-- Name: uq_usuarios_cpf_hash; Type: INDEX; Schema: sige; Owner: -
--

CREATE UNIQUE INDEX uq_usuarios_cpf_hash ON sige.usuarios USING btree (cpf_hash) WHERE ((cpf_hash IS NOT NULL) AND (excluido_em IS NULL));


--
-- Name: agenda_eventos trg_agenda_eventos_atualizado_em; Type: TRIGGER; Schema: sige; Owner: -
--

CREATE TRIGGER trg_agenda_eventos_atualizado_em BEFORE UPDATE ON sige.agenda_eventos FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();


--
-- Name: apoiadores trg_apoiadores_atualizado_em; Type: TRIGGER; Schema: sige; Owner: -
--

CREATE TRIGGER trg_apoiadores_atualizado_em BEFORE UPDATE ON sige.apoiadores FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();


--
-- Name: lideres trg_lideres_atualizado_em; Type: TRIGGER; Schema: sige; Owner: -
--

CREATE TRIGGER trg_lideres_atualizado_em BEFORE UPDATE ON sige.lideres FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();


--
-- Name: usuarios trg_usuarios_atualizado_em; Type: TRIGGER; Schema: sige; Owner: -
--

CREATE TRIGGER trg_usuarios_atualizado_em BEFORE UPDATE ON sige.usuarios FOR EACH ROW EXECUTE FUNCTION sige.fn_atualizar_timestamp();


--
-- Name: agenda_eventos fk_agenda_eventos_criado_por; Type: FK CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.agenda_eventos
    ADD CONSTRAINT fk_agenda_eventos_criado_por FOREIGN KEY (criado_por_usuario_id) REFERENCES sige.usuarios(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: agenda_eventos fk_agenda_eventos_decidido_por; Type: FK CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.agenda_eventos
    ADD CONSTRAINT fk_agenda_eventos_decidido_por FOREIGN KEY (decidido_por) REFERENCES sige.usuarios(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: agenda_eventos fk_agenda_eventos_lider; Type: FK CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.agenda_eventos
    ADD CONSTRAINT fk_agenda_eventos_lider FOREIGN KEY (lider_id) REFERENCES sige.lideres(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: apoiadores fk_apoiadores_criado_por; Type: FK CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.apoiadores
    ADD CONSTRAINT fk_apoiadores_criado_por FOREIGN KEY (criado_por) REFERENCES sige.usuarios(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: apoiadores fk_apoiadores_lider; Type: FK CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.apoiadores
    ADD CONSTRAINT fk_apoiadores_lider FOREIGN KEY (lider_id) REFERENCES sige.lideres(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: lideres fk_lideres_criado_por; Type: FK CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.lideres
    ADD CONSTRAINT fk_lideres_criado_por FOREIGN KEY (criado_por) REFERENCES sige.usuarios(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: logs_auditoria fk_logs_usuario; Type: FK CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.logs_auditoria
    ADD CONSTRAINT fk_logs_usuario FOREIGN KEY (usuario_id) REFERENCES sige.usuarios(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: tokens_refresh fk_tokens_usuario; Type: FK CONSTRAINT; Schema: sige; Owner: -
--

ALTER TABLE ONLY sige.tokens_refresh
    ADD CONSTRAINT fk_tokens_usuario FOREIGN KEY (usuario_id) REFERENCES sige.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict fOuav4PRXDFQX6zHOD8jM4RGj9iEiGODqloAagA9FDUZPzVEzs6R4nLN1w5GpXJ

