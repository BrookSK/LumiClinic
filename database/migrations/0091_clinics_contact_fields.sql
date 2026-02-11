-- Migration: 0091_clinics_contact_fields
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE clinics
    ADD COLUMN contact_email VARCHAR(190) NULL AFTER tenant_key,
    ADD COLUMN contact_phone VARCHAR(64) NULL AFTER contact_email,
    ADD COLUMN contact_whatsapp VARCHAR(64) NULL AFTER contact_phone,
    ADD COLUMN contact_address VARCHAR(255) NULL AFTER contact_whatsapp,
    ADD COLUMN contact_website VARCHAR(255) NULL AFTER contact_address,
    ADD COLUMN contact_instagram VARCHAR(255) NULL AFTER contact_website,
    ADD COLUMN contact_facebook VARCHAR(255) NULL AFTER contact_instagram;
