-- Migration: 0032_medical_record_versions
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS medical_record_versions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    medical_record_id BIGINT UNSIGNED NOT NULL,

    version_no INT UNSIGNED NOT NULL,

    snapshot_json JSON NOT NULL,
    edited_by_user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(64) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_medical_record_versions_record_version (medical_record_id, version_no),
    KEY idx_medical_record_versions_clinic_record (clinic_id, medical_record_id),

    CONSTRAINT fk_medical_record_versions_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_medical_record_versions_record FOREIGN KEY (medical_record_id) REFERENCES medical_records (id),
    CONSTRAINT fk_medical_record_versions_user FOREIGN KEY (edited_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
