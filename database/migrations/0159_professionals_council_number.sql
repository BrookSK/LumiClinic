-- Migration: 0159_professionals_council_number
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.
-- Número do conselho profissional (CRM, CRO, etc.)

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE professionals
    ADD COLUMN council_number VARCHAR(64) NULL AFTER specialty;
