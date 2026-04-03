-- Migration: 0156_users_billing_profile
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.
-- Campos do contratante (quem contrata o sistema), editáveis no /me e pelo superadmin.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE users
    ADD COLUMN phone VARCHAR(32) NULL AFTER email,
    ADD COLUMN doc_type VARCHAR(8) NULL AFTER phone,
    ADD COLUMN doc_number VARCHAR(32) NULL AFTER doc_type,
    ADD COLUMN postal_code VARCHAR(16) NULL AFTER doc_number,
    ADD COLUMN address_street VARCHAR(190) NULL AFTER postal_code,
    ADD COLUMN address_number VARCHAR(16) NULL AFTER address_street,
    ADD COLUMN address_complement VARCHAR(100) NULL AFTER address_number,
    ADD COLUMN address_neighborhood VARCHAR(100) NULL AFTER address_complement,
    ADD COLUMN address_city VARCHAR(100) NULL AFTER address_neighborhood,
    ADD COLUMN address_state VARCHAR(4) NULL AFTER address_city;
