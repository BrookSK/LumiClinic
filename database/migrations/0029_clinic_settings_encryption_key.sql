-- Migration: 0029_clinic_settings_encryption_key
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE clinic_settings
    ADD COLUMN encryption_key VARCHAR(255) NULL AFTER language;

-- Gera chave para clínicas já existentes (hex 32 bytes = 64 chars)
UPDATE clinic_settings
   SET encryption_key = LOWER(HEX(RANDOM_BYTES(32)))
 WHERE encryption_key IS NULL
   AND deleted_at IS NULL;
