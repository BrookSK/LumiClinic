-- Migration: 0093_legal_documents_and_acceptances
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS legal_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NULL,

    scope VARCHAR(32) NOT NULL, -- patient_portal|system_user
    target_role_code VARCHAR(64) NULL, -- optional (system_user)

    title VARCHAR(190) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_legal_documents_scope (scope, status),
    KEY idx_legal_documents_clinic (clinic_id, scope, status),

    CONSTRAINT fk_legal_documents_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS legal_document_acceptances (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NULL,
    document_id BIGINT UNSIGNED NOT NULL,

    patient_user_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,

    accepted_at DATETIME NOT NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_lda_doc_patient_user (document_id, patient_user_id),
    UNIQUE KEY uq_lda_doc_user (document_id, user_id),
    KEY idx_lda_patient_user (clinic_id, patient_user_id),
    KEY idx_lda_user (clinic_id, user_id),

    CONSTRAINT fk_lda_doc FOREIGN KEY (document_id) REFERENCES legal_documents (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
