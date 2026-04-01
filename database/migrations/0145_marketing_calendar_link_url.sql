-- Migration: 0145_marketing_calendar_link_url
-- Adiciona campo link_url na tabela de calendário de marketing (item 14.1 do escopo)
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE marketing_calendar_entries
    ADD COLUMN IF NOT EXISTS link_url VARCHAR(500) NULL AFTER notes;
