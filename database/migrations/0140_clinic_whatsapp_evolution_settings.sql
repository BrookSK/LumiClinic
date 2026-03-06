-- Migration: 0140_clinic_whatsapp_evolution_settings
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE clinic_settings
    ADD COLUMN evolution_instance VARCHAR(190) NULL AFTER zapi_token_encrypted,
    ADD COLUMN evolution_apikey_encrypted TEXT NULL AFTER evolution_instance;
