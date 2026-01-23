-- Migration: 0062_patient_lgpd_requests
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS patient_lgpd_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    type VARCHAR(32) NOT NULL, -- export|delete|revoke_consent
    status VARCHAR(32) NOT NULL DEFAULT 'pending', -- pending|processed|rejected

    note VARCHAR(255) NULL,

    processed_by_user_id BIGINT UNSIGNED NULL,
    processed_at DATETIME NULL,
    processed_note VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_plr_patient (clinic_id, patient_id),
    KEY idx_plr_status (clinic_id, status),

    CONSTRAINT fk_plr_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_plr_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_plr_processed_by FOREIGN KEY (processed_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
