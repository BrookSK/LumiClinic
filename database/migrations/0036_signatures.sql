-- Migration: 0036_signatures
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS signatures (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    term_acceptance_id BIGINT UNSIGNED NULL,
    medical_record_id BIGINT UNSIGNED NULL,

    storage_path VARCHAR(512) NOT NULL,
    mime_type VARCHAR(64) NOT NULL DEFAULT 'image/png',

    signed_by_user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(64) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_signatures_patient (clinic_id, patient_id),

    CONSTRAINT fk_signatures_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_signatures_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_signatures_term_acceptance FOREIGN KEY (term_acceptance_id) REFERENCES consent_acceptances (id),
    CONSTRAINT fk_signatures_medical_record FOREIGN KEY (medical_record_id) REFERENCES medical_records (id),
    CONSTRAINT fk_signatures_user FOREIGN KEY (signed_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
