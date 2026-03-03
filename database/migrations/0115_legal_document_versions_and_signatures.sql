-- Migration: 0115_legal_document_versions_and_signatures
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE legal_document_acceptances
    ADD COLUMN document_version_id BIGINT UNSIGNED NULL AFTER document_id,
    ADD KEY idx_lda_document_version (document_version_id);

CREATE TABLE IF NOT EXISTS legal_document_versions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NULL,
    document_id BIGINT UNSIGNED NOT NULL,

    version_number INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    hash_sha256 CHAR(64) NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_ldv_doc_version (document_id, version_number),
    UNIQUE KEY uq_ldv_doc_hash (document_id, hash_sha256),
    KEY idx_ldv_clinic_doc (clinic_id, document_id),

    CONSTRAINT fk_ldv_document FOREIGN KEY (document_id) REFERENCES legal_documents (id),
    CONSTRAINT fk_ldv_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS legal_document_signatures (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NULL,

    document_id BIGINT UNSIGNED NOT NULL,
    document_version_id BIGINT UNSIGNED NOT NULL,

    patient_user_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,

    method VARCHAR(32) NOT NULL DEFAULT 'draw',
    signature_data_url MEDIUMTEXT NOT NULL,
    signature_hash_sha256 CHAR(64) NOT NULL,

    signed_at DATETIME NOT NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_lds_docver_patient_user (document_version_id, patient_user_id),
    UNIQUE KEY uq_lds_docver_user (document_version_id, user_id),
    KEY idx_lds_patient_user (clinic_id, patient_user_id),
    KEY idx_lds_user (clinic_id, user_id),

    CONSTRAINT fk_lds_document FOREIGN KEY (document_id) REFERENCES legal_documents (id),
    CONSTRAINT fk_lds_version FOREIGN KEY (document_version_id) REFERENCES legal_document_versions (id),
    CONSTRAINT fk_lds_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
