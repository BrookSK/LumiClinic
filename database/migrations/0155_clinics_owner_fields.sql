-- Migration: 0155_clinics_owner_fields
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE clinics
    ADD COLUMN owner_name VARCHAR(190) NULL AFTER cnpj,
    ADD COLUMN owner_phone VARCHAR(32) NULL AFTER owner_name,
    ADD COLUMN owner_doc_type VARCHAR(8) NULL AFTER owner_phone,
    ADD COLUMN owner_postal_code VARCHAR(16) NULL AFTER owner_doc_type,
    ADD COLUMN owner_street VARCHAR(190) NULL AFTER owner_postal_code,
    ADD COLUMN owner_number VARCHAR(16) NULL AFTER owner_street,
    ADD COLUMN owner_complement VARCHAR(100) NULL AFTER owner_number,
    ADD COLUMN owner_neighborhood VARCHAR(100) NULL AFTER owner_complement,
    ADD COLUMN owner_city VARCHAR(100) NULL AFTER owner_neighborhood,
    ADD COLUMN owner_state VARCHAR(4) NULL AFTER owner_city;
