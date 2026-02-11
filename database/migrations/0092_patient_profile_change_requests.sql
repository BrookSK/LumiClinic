-- Migration: 0092_patient_profile_change_requests
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS patient_profile_change_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    patient_user_id BIGINT UNSIGNED NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    requested_fields_json JSON NOT NULL,

    reviewed_by_user_id BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_notes TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_ppcr_clinic_status (clinic_id, status),
    KEY idx_ppcr_patient (clinic_id, patient_id),
    KEY idx_ppcr_created_at (created_at),

    CONSTRAINT fk_ppcr_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_ppcr_patient FOREIGN KEY (patient_id) REFERENCES patients (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
