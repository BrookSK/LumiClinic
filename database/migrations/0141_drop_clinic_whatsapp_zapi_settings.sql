-- Migration: 0141_drop_clinic_whatsapp_zapi_settings
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

UPDATE clinic_settings
   SET evolution_instance = IF(evolution_instance IS NULL OR TRIM(evolution_instance) = '', zapi_instance_id, evolution_instance),
       evolution_apikey_encrypted = IF(evolution_apikey_encrypted IS NULL OR TRIM(evolution_apikey_encrypted) = '', zapi_token_encrypted, evolution_apikey_encrypted)
 WHERE deleted_at IS NULL;

ALTER TABLE clinic_settings
    DROP COLUMN zapi_token_encrypted,
    DROP COLUMN zapi_instance_id;
