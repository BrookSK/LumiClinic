-- Migration: 0118_patients_whatsapp_opt_in
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE patients
    ADD COLUMN whatsapp_opt_in TINYINT(1) NOT NULL DEFAULT 1 AFTER phone,
    ADD COLUMN whatsapp_opt_in_updated_at DATETIME NULL AFTER whatsapp_opt_in;
