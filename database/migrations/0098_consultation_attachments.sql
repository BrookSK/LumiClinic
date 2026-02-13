-- Migration: 0098_consultation_attachments
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS consultation_attachments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    consultation_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    storage_path VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NULL,
    mime_type VARCHAR(190) NULL,
    size_bytes INT NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_consultation_attachments_consultation (consultation_id, id),
    KEY idx_consultation_attachments_patient (patient_id, id),
    KEY idx_consultation_attachments_deleted_at (deleted_at),

    CONSTRAINT fk_consultation_attachments_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_consultation_attachments_consultation FOREIGN KEY (consultation_id) REFERENCES consultations (id),
    CONSTRAINT fk_consultation_attachments_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_consultation_attachments_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
