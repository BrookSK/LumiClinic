-- Migration: 0035_consent_terms
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS consent_terms (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    procedure_type VARCHAR(190) NOT NULL,
    title VARCHAR(190) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_consent_terms_clinic (clinic_id, procedure_type),

    CONSTRAINT fk_consent_terms_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consent_acceptances (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    term_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    procedure_type VARCHAR(190) NOT NULL,

    accepted_by_user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(64) NULL,
    accepted_at DATETIME NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_consent_acceptances_patient (clinic_id, patient_id),
    KEY idx_consent_acceptances_term (term_id),

    CONSTRAINT fk_consent_acceptances_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_consent_acceptances_term FOREIGN KEY (term_id) REFERENCES consent_terms (id),
    CONSTRAINT fk_consent_acceptances_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_consent_acceptances_user FOREIGN KEY (accepted_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
