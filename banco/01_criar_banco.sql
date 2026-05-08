-- =============================================================
-- SIGE - Sistema de Mapeamento Político
-- Script 01: Criação do banco de dados
-- =============================================================

-- Executa conectado ao banco postgres (padrão)
-- Execução: psql -h localhost -U postgres -f 01_criar_banco.sql

DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_database WHERE datname = 'sige_db') THEN
        PERFORM dblink_exec('dbname=postgres', '
            CREATE DATABASE sige_db
            WITH
                OWNER       = postgres
                ENCODING    = ''UTF8''
                CONNECTION LIMIT = -1
        ');
    END IF;
END
$$;
