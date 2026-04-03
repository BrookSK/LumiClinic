-- Migration: 0154_clinics_cnpj
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE clinics
    ADD COLUMN cnpj VARCHAR(32) NULL AFTER name;
