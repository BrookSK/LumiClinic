-- Migration: 0137_medical_record_audio_notes
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS medical_record_audio_notes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    medical_record_id BIGINT UNSIGNED NULL,
    appointment_id BIGINT UNSIGNED NULL,
    professional_id BIGINT UNSIGNED NULL,

    storage_path VARCHAR(500) NOT NULL,
    original_filename VARCHAR(255) NULL,
    mime_type VARCHAR(120) NULL,
    size_bytes BIGINT UNSIGNED NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'uploaded',
    transcript_text MEDIUMTEXT NULL,
    transcribed_at DATETIME NULL,

    created_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_mran_clinic_patient (clinic_id, patient_id),
    KEY idx_mran_medical_record (medical_record_id),
    KEY idx_mran_appointment (appointment_id),
    KEY idx_mran_professional (professional_id),
    KEY idx_mran_deleted_at (deleted_at),

    CONSTRAINT fk_mran_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_mran_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_mran_medical_record FOREIGN KEY (medical_record_id) REFERENCES medical_records (id),
    CONSTRAINT fk_mran_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id),
    CONSTRAINT fk_mran_professional FOREIGN KEY (professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_mran_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
