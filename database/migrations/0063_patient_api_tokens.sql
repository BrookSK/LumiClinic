-- Migration: 0063_patient_api_tokens
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS patient_api_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_user_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    token_hash VARCHAR(255) NOT NULL,
    name VARCHAR(190) NULL,
    scopes_json JSON NULL,

    expires_at DATETIME NULL,
    last_used_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_pat_hash (token_hash),
    KEY idx_pat_patient (clinic_id, patient_id),
    KEY idx_pat_user (clinic_id, patient_user_id),
    KEY idx_pat_revoked (revoked_at),

    CONSTRAINT fk_pat_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_pat_patient_user FOREIGN KEY (patient_user_id) REFERENCES patient_users (id),
    CONSTRAINT fk_pat_patient FOREIGN KEY (patient_id) REFERENCES patients (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
