-- Migration: 0030_patients_clinical_fields_and_cpf_encrypted
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE patients
    ADD COLUMN birth_date DATE NULL AFTER name,
    ADD COLUMN sex VARCHAR(16) NULL AFTER birth_date,
    ADD COLUMN cpf_encrypted VARBINARY(512) NULL AFTER sex,
    ADD COLUMN cpf_last4 VARCHAR(4) NULL AFTER cpf_encrypted,
    ADD COLUMN address TEXT NULL AFTER phone,
    ADD COLUMN notes TEXT NULL AFTER address,
    ADD COLUMN reference_professional_id BIGINT UNSIGNED NULL AFTER notes;

CREATE INDEX idx_patients_clinic_name ON patients (clinic_id, name);
CREATE INDEX idx_patients_clinic_phone ON patients (clinic_id, phone);
CREATE INDEX idx_patients_clinic_birth_date ON patients (clinic_id, birth_date);
CREATE INDEX idx_patients_clinic_cpf_last4 ON patients (clinic_id, cpf_last4);

ALTER TABLE patients
    ADD CONSTRAINT fk_patients_reference_professional
        FOREIGN KEY (reference_professional_id)
        REFERENCES professionals (id);
