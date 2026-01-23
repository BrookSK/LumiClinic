-- Migration: 0055_patient_portal_auth
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS patient_users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,

    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    two_factor_secret VARCHAR(255) NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'active',

    last_login_at DATETIME NULL,
    last_login_ip VARCHAR(64) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_patient_users_email_clinic (clinic_id, email),
    KEY idx_patient_users_patient (clinic_id, patient_id),
    KEY idx_patient_users_deleted_at (deleted_at),

    CONSTRAINT fk_patient_users_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_patient_users_patient FOREIGN KEY (patient_id) REFERENCES patients (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_password_resets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_user_id BIGINT UNSIGNED NOT NULL,

    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,

    created_ip VARCHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_patient_pwreset_user (clinic_id, patient_user_id),
    KEY idx_patient_pwreset_expires (expires_at),

    CONSTRAINT fk_patient_pwreset_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_patient_pwreset_user FOREIGN KEY (patient_user_id) REFERENCES patient_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
