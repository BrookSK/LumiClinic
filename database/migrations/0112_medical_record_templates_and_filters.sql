-- Migration: 0112_medical_record_templates_and_filters
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS medical_record_templates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_mrt_clinic (clinic_id),
    CONSTRAINT fk_mrt_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS medical_record_template_fields (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED NOT NULL,

    field_key VARCHAR(64) NOT NULL,
    label VARCHAR(190) NOT NULL,
    field_type ENUM('text','textarea','checkbox','select','number','date') NOT NULL,
    required TINYINT(1) NOT NULL DEFAULT 0,
    options_json JSON NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_mrtf_template_key (template_id, field_key),
    KEY idx_mrtf_template (template_id, sort_order),

    CONSTRAINT fk_mrtf_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_mrtf_template FOREIGN KEY (template_id) REFERENCES medical_record_templates (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE medical_records
    ADD COLUMN template_id BIGINT UNSIGNED NULL AFTER procedure_type,
    ADD COLUMN template_name_snapshot VARCHAR(190) NULL AFTER template_id,
    ADD COLUMN template_updated_at_snapshot DATETIME NULL AFTER template_name_snapshot,
    ADD COLUMN template_fields_snapshot_json JSON NULL AFTER template_updated_at_snapshot,
    ADD COLUMN fields_json JSON NULL AFTER template_fields_snapshot_json,
    ADD KEY idx_medical_records_template (clinic_id, patient_id, template_id);

ALTER TABLE medical_records
    ADD CONSTRAINT fk_medical_records_template FOREIGN KEY (template_id) REFERENCES medical_record_templates (id);
