-- Migration: 0057_patient_uploads
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS patient_uploads (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    patient_user_id BIGINT UNSIGNED NULL,

    kind VARCHAR(32) NOT NULL DEFAULT 'other', -- before|after|other
    taken_at DATETIME NULL,
    note VARCHAR(255) NULL,

    storage_path VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NULL,
    mime_type VARCHAR(128) NULL,
    size_bytes INT NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'pending', -- pending|approved|rejected
    moderated_by_user_id BIGINT UNSIGNED NULL,
    moderated_at DATETIME NULL,
    moderation_note VARCHAR(255) NULL,

    medical_image_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_pu_clinic (clinic_id),
    KEY idx_pu_patient (clinic_id, patient_id),
    KEY idx_pu_status (clinic_id, status),
    KEY idx_pu_medical_image (clinic_id, medical_image_id),

    CONSTRAINT fk_pu_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_pu_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_pu_patient_user FOREIGN KEY (patient_user_id) REFERENCES patient_users (id),
    CONSTRAINT fk_pu_moderated_by FOREIGN KEY (moderated_by_user_id) REFERENCES users (id),
    CONSTRAINT fk_pu_medical_image FOREIGN KEY (medical_image_id) REFERENCES medical_images (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
