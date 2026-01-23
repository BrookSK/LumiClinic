-- Migration: 0050_patients_cpf_plain
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE patients
    ADD COLUMN cpf VARCHAR(32) NULL AFTER sex;

CREATE INDEX idx_patients_clinic_cpf ON patients (clinic_id, cpf);
