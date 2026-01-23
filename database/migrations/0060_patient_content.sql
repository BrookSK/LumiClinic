-- Migration: 0060_patient_content
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS patient_contents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    type VARCHAR(32) NOT NULL DEFAULT 'link', -- link|pdf|video
    title VARCHAR(190) NOT NULL,
    description VARCHAR(255) NULL,

    url VARCHAR(255) NULL,
    storage_path VARCHAR(255) NULL,
    mime_type VARCHAR(128) NULL,

    procedure_type VARCHAR(64) NULL,
    audience VARCHAR(64) NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_pc_clinic (clinic_id),
    KEY idx_pc_status (clinic_id, status),
    KEY idx_pc_type (clinic_id, type),

    CONSTRAINT fk_pc_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_pc_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_content_access (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    content_id BIGINT UNSIGNED NOT NULL,

    granted_by_user_id BIGINT UNSIGNED NULL,
    granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_pca (clinic_id, patient_id, content_id),
    KEY idx_pca_patient (clinic_id, patient_id),
    KEY idx_pca_content (clinic_id, content_id),

    CONSTRAINT fk_pca_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_pca_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_pca_content FOREIGN KEY (content_id) REFERENCES patient_contents (id),
    CONSTRAINT fk_pca_granted_by FOREIGN KEY (granted_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
