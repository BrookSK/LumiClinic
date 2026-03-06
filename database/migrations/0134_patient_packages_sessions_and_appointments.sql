-- Migration: 0134_patient_packages_sessions_and_appointments
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE appointments
    ADD COLUMN patient_package_id BIGINT UNSIGNED NULL AFTER patient_procedure_id,
    ADD KEY idx_appt_patient_package (patient_package_id),
    ADD CONSTRAINT fk_appt_patient_package
        FOREIGN KEY (patient_package_id) REFERENCES patient_packages (id);

CREATE TABLE IF NOT EXISTS appointment_package_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    appointment_id BIGINT UNSIGNED NOT NULL,
    patient_package_id BIGINT UNSIGNED NOT NULL,

    consumed_at DATETIME NOT NULL,
    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uniq_appt_pkg_session_appointment (appointment_id),
    KEY idx_appt_pkg_session_clinic (clinic_id),
    KEY idx_appt_pkg_session_patient_package (patient_package_id),

    CONSTRAINT fk_appt_pkg_session_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_appt_pkg_session_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id),
    CONSTRAINT fk_appt_pkg_session_patient_package FOREIGN KEY (patient_package_id) REFERENCES patient_packages (id),
    CONSTRAINT fk_appt_pkg_session_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
