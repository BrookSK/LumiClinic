-- Migration: 0061_patient_events
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS patient_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    event_code VARCHAR(64) NOT NULL, -- appointment_confirmed|appointment_completed|no_show|portal_login|etc
    reference_type VARCHAR(32) NULL,
    reference_id BIGINT UNSIGNED NULL,

    meta_json JSON NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_pe_clinic (clinic_id),
    KEY idx_pe_patient (clinic_id, patient_id),
    KEY idx_pe_event (clinic_id, event_code),

    CONSTRAINT fk_pe_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_pe_patient FOREIGN KEY (patient_id) REFERENCES patients (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
