-- Migration: 0157_clinics_address_fields
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.
-- Campos de endereço separados para a clínica (substituem contact_address texto livre).

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE clinics
    ADD COLUMN address_street VARCHAR(190) NULL AFTER contact_address,
    ADD COLUMN address_number VARCHAR(16) NULL AFTER address_street,
    ADD COLUMN address_complement VARCHAR(100) NULL AFTER address_number,
    ADD COLUMN address_neighborhood VARCHAR(100) NULL AFTER address_complement,
    ADD COLUMN address_city VARCHAR(100) NULL AFTER address_neighborhood,
    ADD COLUMN address_state VARCHAR(4) NULL AFTER address_city,
    ADD COLUMN address_zip VARCHAR(16) NULL AFTER address_state;
