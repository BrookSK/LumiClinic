-- Migration: 0114_clinic_ai_openai_key
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE clinic_settings
    ADD COLUMN openai_api_key_encrypted TEXT NULL AFTER encryption_key;
