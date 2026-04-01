-- Migration: 0146_marketing_calendar_links_json
-- Amplia link_url para TEXT para suportar múltiplos links em JSON
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE marketing_calendar_entries
    MODIFY COLUMN link_url TEXT NULL;
