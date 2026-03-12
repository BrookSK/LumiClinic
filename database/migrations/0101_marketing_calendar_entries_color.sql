-- Migration: 0101_marketing_calendar_entries_color
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE marketing_calendar_entries
    ADD COLUMN color VARCHAR(7) NULL AFTER status;
