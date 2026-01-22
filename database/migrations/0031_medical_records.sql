-- Migration: 0031_medical_records
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS medical_records (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED NULL,

    attended_at DATETIME NOT NULL,
    procedure_type VARCHAR(190) NULL,

    clinical_description MEDIUMTEXT NULL,
    clinical_evolution MEDIUMTEXT NULL,
    notes TEXT NULL,

    ai_transcript MEDIUMTEXT NULL,
    ai_summary MEDIUMTEXT NULL,
    ai_report MEDIUMTEXT NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_medical_records_clinic_patient (clinic_id, patient_id),
    KEY idx_medical_records_attended_at (clinic_id, attended_at),
    KEY idx_medical_records_deleted_at (deleted_at),

    CONSTRAINT fk_medical_records_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_medical_records_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_medical_records_professional FOREIGN KEY (professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_medical_records_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
