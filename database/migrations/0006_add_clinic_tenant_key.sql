-- Migration: 0006_add_clinic_tenant_key
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE clinics
    ADD COLUMN tenant_key VARCHAR(64) NULL AFTER name;

CREATE UNIQUE INDEX uq_clinics_tenant_key ON clinics (tenant_key);
