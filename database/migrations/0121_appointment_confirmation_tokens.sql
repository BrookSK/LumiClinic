-- Migration: 0121_appointment_confirmation_tokens
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS appointment_confirmation_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    appointment_id BIGINT UNSIGNED NOT NULL,

    kind VARCHAR(32) NOT NULL DEFAULT 'confirm',

    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,

    used_at DATETIME NULL,
    used_action VARCHAR(32) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_appt_confirm_token_hash (token_hash),
    KEY idx_appt_confirm_clinic_appt_kind (clinic_id, appointment_id, kind),
    KEY idx_appt_confirm_expires (expires_at),
    CONSTRAINT fk_appt_confirm_tokens_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_appt_confirm_tokens_appt FOREIGN KEY (appointment_id) REFERENCES appointments (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
