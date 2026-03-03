-- Migration: 0110_patient_clinical_sheet
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS patient_allergies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    type VARCHAR(32) NOT NULL,
    trigger_name VARCHAR(190) NOT NULL,
    reaction VARCHAR(190) NULL,
    severity VARCHAR(32) NULL,
    notes TEXT NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_patient_allergies_clinic_patient (clinic_id, patient_id, id),
    KEY idx_patient_allergies_deleted_at (deleted_at),

    CONSTRAINT fk_patient_allergies_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_patient_allergies_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_patient_allergies_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_conditions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    condition_name VARCHAR(190) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    onset_date DATE NULL,
    notes TEXT NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_patient_conditions_clinic_patient (clinic_id, patient_id, id),
    KEY idx_patient_conditions_status (clinic_id, status, id),
    KEY idx_patient_conditions_deleted_at (deleted_at),

    CONSTRAINT fk_patient_conditions_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_patient_conditions_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_patient_conditions_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_clinical_alerts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(190) NOT NULL,
    details TEXT NULL,
    severity VARCHAR(32) NOT NULL DEFAULT 'warning',
    active TINYINT(1) NOT NULL DEFAULT 1,

    resolved_at DATETIME NULL,
    resolved_by_user_id BIGINT UNSIGNED NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_patient_clinical_alerts_clinic_patient (clinic_id, patient_id, id),
    KEY idx_patient_clinical_alerts_active (clinic_id, patient_id, active, id),
    KEY idx_patient_clinical_alerts_severity (clinic_id, severity, id),
    KEY idx_patient_clinical_alerts_deleted_at (deleted_at),

    CONSTRAINT fk_patient_clinical_alerts_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_patient_clinical_alerts_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_patient_clinical_alerts_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id),
    CONSTRAINT fk_patient_clinical_alerts_resolved_by FOREIGN KEY (resolved_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
