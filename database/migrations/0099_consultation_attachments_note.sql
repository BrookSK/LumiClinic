-- Migration: 0099_consultation_attachments_note
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE consultation_attachments
    ADD COLUMN note VARCHAR(255) NULL AFTER patient_id;
