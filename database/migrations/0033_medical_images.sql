-- Migration: 0033_medical_images
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS medical_images (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    medical_record_id BIGINT UNSIGNED NULL,
    professional_id BIGINT UNSIGNED NULL,

    kind ENUM('before','after','other') NOT NULL DEFAULT 'other',
    taken_at DATETIME NULL,
    procedure_type VARCHAR(190) NULL,

    storage_path VARCHAR(512) NOT NULL,
    original_filename VARCHAR(255) NULL,
    mime_type VARCHAR(128) NULL,
    size_bytes BIGINT UNSIGNED NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_medical_images_clinic_patient (clinic_id, patient_id),
    KEY idx_medical_images_record (medical_record_id),
    KEY idx_medical_images_deleted_at (deleted_at),

    CONSTRAINT fk_medical_images_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_medical_images_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_medical_images_record FOREIGN KEY (medical_record_id) REFERENCES medical_records (id),
    CONSTRAINT fk_medical_images_professional FOREIGN KEY (professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_medical_images_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
