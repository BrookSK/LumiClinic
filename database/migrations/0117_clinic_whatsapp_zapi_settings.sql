-- Migration: 0117_clinic_whatsapp_zapi_settings
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE clinic_settings
    ADD COLUMN zapi_instance_id VARCHAR(190) NULL AFTER encryption_key,
    ADD COLUMN zapi_token_encrypted TEXT NULL AFTER zapi_instance_id;
