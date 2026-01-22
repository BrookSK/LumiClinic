-- Migration: 0034_anamnesis
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS anamnesis_templates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_anamnesis_templates_clinic (clinic_id),
    CONSTRAINT fk_anamnesis_templates_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS anamnesis_template_fields (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED NOT NULL,

    field_key VARCHAR(64) NOT NULL,
    label VARCHAR(190) NOT NULL,
    field_type ENUM('text','textarea','checkbox','select') NOT NULL,
    options_json JSON NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_anamnesis_field_template_key (template_id, field_key),
    KEY idx_anamnesis_fields_template (template_id, sort_order),

    CONSTRAINT fk_anamnesis_fields_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_anamnesis_fields_template FOREIGN KEY (template_id) REFERENCES anamnesis_templates (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS anamnesis_responses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED NULL,

    answers_json JSON NOT NULL,

    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_anamnesis_responses_patient (clinic_id, patient_id),
    KEY idx_anamnesis_responses_template (template_id),

    CONSTRAINT fk_anamnesis_responses_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_anamnesis_responses_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_anamnesis_responses_template FOREIGN KEY (template_id) REFERENCES anamnesis_templates (id),
    CONSTRAINT fk_anamnesis_responses_professional FOREIGN KEY (professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_anamnesis_responses_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
