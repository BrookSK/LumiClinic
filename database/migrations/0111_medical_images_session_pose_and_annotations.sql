-- Migration: 0111_medical_images_session_pose_and_annotations
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE medical_images
    ADD COLUMN session_number INT NULL AFTER procedure_type,
    ADD COLUMN pose VARCHAR(64) NULL AFTER session_number,
    ADD KEY idx_mi_procedure_session (clinic_id, patient_id, procedure_type, session_number);

CREATE TABLE IF NOT EXISTS medical_image_annotations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    medical_image_id BIGINT UNSIGNED NOT NULL,

    payload_json JSON NOT NULL,
    note VARCHAR(255) NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_mia_clinic_image (clinic_id, medical_image_id),
    KEY idx_mia_deleted_at (deleted_at),

    CONSTRAINT fk_mia_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_mia_medical_image FOREIGN KEY (medical_image_id) REFERENCES medical_images (id),
    CONSTRAINT fk_mia_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
