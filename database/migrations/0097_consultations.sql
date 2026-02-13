-- Migration: 0097_consultations
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS consultations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    appointment_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED NOT NULL,

    executed_at DATETIME NOT NULL,
    notes TEXT NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uniq_consultations_appointment (clinic_id, appointment_id),
    KEY idx_consultations_patient (clinic_id, patient_id, executed_at),
    KEY idx_consultations_professional (clinic_id, professional_id, executed_at),
    KEY idx_consultations_deleted_at (deleted_at),

    CONSTRAINT fk_consultations_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_consultations_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id),
    CONSTRAINT fk_consultations_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_consultations_professional FOREIGN KEY (professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_consultations_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
