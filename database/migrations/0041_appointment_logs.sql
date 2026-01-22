-- Migration: 0041_appointment_logs
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS appointment_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    appointment_id BIGINT UNSIGNED NOT NULL,

    action VARCHAR(64) NOT NULL,
    from_json JSON NULL,
    to_json JSON NULL,

    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(64) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_appt_logs_clinic_appt (clinic_id, appointment_id, id),
    KEY idx_appt_logs_action (action),

    CONSTRAINT fk_appt_logs_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_appt_logs_appt FOREIGN KEY (appointment_id) REFERENCES appointments (id),
    CONSTRAINT fk_appt_logs_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
