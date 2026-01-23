-- Migration: 0059_patient_notifications
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS patient_notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    channel VARCHAR(32) NOT NULL DEFAULT 'in_app', -- in_app|email|whatsapp
    type VARCHAR(64) NOT NULL, -- appointment_confirmed|payment_due|etc

    title VARCHAR(190) NOT NULL,
    body TEXT NOT NULL,

    reference_type VARCHAR(32) NULL,
    reference_id BIGINT UNSIGNED NULL,

    read_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_pn_clinic (clinic_id),
    KEY idx_pn_patient (clinic_id, patient_id),
    KEY idx_pn_read (clinic_id, patient_id, read_at),
    KEY idx_pn_type (clinic_id, type),

    CONSTRAINT fk_pn_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_pn_patient FOREIGN KEY (patient_id) REFERENCES patients (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
