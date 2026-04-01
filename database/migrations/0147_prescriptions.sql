-- Migration: 0147_prescriptions
-- Receituário digital simples
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS prescriptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED NULL,
    medical_record_id BIGINT UNSIGNED NULL,

    title VARCHAR(200) NOT NULL DEFAULT 'Receita',
    body TEXT NOT NULL,

    issued_at DATE NOT NULL,

    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_prescriptions_clinic_patient (clinic_id, patient_id),
    KEY idx_prescriptions_deleted_at (deleted_at),

    CONSTRAINT fk_prescriptions_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_prescriptions_patient FOREIGN KEY (patient_id) REFERENCES patients (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
