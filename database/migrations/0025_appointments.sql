-- Migration: 0025_appointments
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS appointments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NULL,

    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'scheduled',
    origin VARCHAR(32) NOT NULL DEFAULT 'reception',

    notes VARCHAR(255) NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_appt_clinic_start (clinic_id, start_at),
    KEY idx_appt_prof_start (professional_id, start_at),
    KEY idx_appt_status (status),
    KEY idx_appt_deleted_at (deleted_at),

    CONSTRAINT fk_appt_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_appt_professional FOREIGN KEY (professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_appt_service FOREIGN KEY (service_id) REFERENCES services (id),
    CONSTRAINT fk_appt_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_appt_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
