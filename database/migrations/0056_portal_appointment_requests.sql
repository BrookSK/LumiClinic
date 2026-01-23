-- Migration: 0056_portal_appointment_requests
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS patient_appointment_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    appointment_id BIGINT UNSIGNED NOT NULL,

    type VARCHAR(32) NOT NULL, -- reschedule|cancel
    status VARCHAR(32) NOT NULL DEFAULT 'pending', -- pending|approved|rejected|cancelled

    requested_start_at DATETIME NULL,
    note VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_par_clinic (clinic_id),
    KEY idx_par_patient (clinic_id, patient_id),
    KEY idx_par_appt (clinic_id, appointment_id),
    KEY idx_par_status (clinic_id, status),

    CONSTRAINT fk_par_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_par_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_par_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
