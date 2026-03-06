-- Migration: 0138_appointment_anamnesis_requests_and_default_template
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- clinic_settings: default template for pre-consult anamnesis
SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'clinic_settings'
      AND COLUMN_NAME = 'anamnesis_default_template_id'
);

SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE clinic_settings ADD COLUMN anamnesis_default_template_id BIGINT UNSIGNED NULL AFTER zapi_token_encrypted',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'clinic_settings'
      AND INDEX_NAME = 'idx_clinic_settings_anamnesis_default'
);

SET @sql := IF(
    @idx_exists = 0,
    'ALTER TABLE clinic_settings ADD KEY idx_clinic_settings_anamnesis_default (clinic_id, anamnesis_default_template_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Requests per appointment (public link)
CREATE TABLE IF NOT EXISTS appointment_anamnesis_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    appointment_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED NOT NULL,

    token_hash CHAR(64) NOT NULL,
    token_encrypted TEXT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    used_action VARCHAR(32) NULL,

    response_id BIGINT UNSIGNED NULL,

    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_a_ar_token_hash (token_hash),
    KEY idx_a_ar_appt (clinic_id, appointment_id),
    KEY idx_a_ar_patient (clinic_id, patient_id),
    KEY idx_a_ar_template (clinic_id, template_id),
    KEY idx_a_ar_expires (clinic_id, expires_at),

    CONSTRAINT fk_a_ar_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_a_ar_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id),
    CONSTRAINT fk_a_ar_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_a_ar_template FOREIGN KEY (template_id) REFERENCES anamnesis_templates (id),
    CONSTRAINT fk_a_ar_response FOREIGN KEY (response_id) REFERENCES anamnesis_responses (id),
    CONSTRAINT fk_a_ar_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
