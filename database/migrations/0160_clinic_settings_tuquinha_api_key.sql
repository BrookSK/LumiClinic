-- Migration: 0160_clinic_settings_tuquinha_api_key
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE clinic_settings
    ADD COLUMN tuquinha_api_key VARCHAR(255) NULL AFTER language;
