-- Migration: 0106_operational_configuration_funnel_and_origins
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Etapas do funil (por clínica)
CREATE TABLE IF NOT EXISTS clinic_funnel_stages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(80) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status VARCHAR(16) NOT NULL DEFAULT 'ativo',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_cfs_clinic (clinic_id),
    KEY idx_cfs_clinic_status_sort (clinic_id, status, sort_order),
    UNIQUE KEY uniq_cfs_clinic_name (clinic_id, name),

    CONSTRAINT fk_cfs_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Motivos de perda (por clínica)
CREATE TABLE IF NOT EXISTS clinic_lost_reasons (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status VARCHAR(16) NOT NULL DEFAULT 'ativo',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_clr_clinic (clinic_id),
    KEY idx_clr_clinic_status_sort (clinic_id, status, sort_order),
    UNIQUE KEY uniq_clr_clinic_name (clinic_id, name),

    CONSTRAINT fk_clr_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Origem do paciente (por clínica)
CREATE TABLE IF NOT EXISTS clinic_patient_origins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status VARCHAR(16) NOT NULL DEFAULT 'ativo',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_cpo_clinic (clinic_id),
    KEY idx_cpo_clinic_status_sort (clinic_id, status, sort_order),
    UNIQUE KEY uniq_cpo_clinic_name (clinic_id, name),

    CONSTRAINT fk_cpo_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campos operacionais em Pacientes e Agendamentos
ALTER TABLE patients
    ADD COLUMN patient_origin_id BIGINT UNSIGNED NULL AFTER reference_professional_id,
    ADD KEY idx_patients_origin (clinic_id, patient_origin_id),
    ADD CONSTRAINT fk_patients_origin FOREIGN KEY (patient_origin_id) REFERENCES clinic_patient_origins (id);

ALTER TABLE appointments
    ADD COLUMN funnel_stage_id BIGINT UNSIGNED NULL AFTER origin,
    ADD COLUMN lost_reason_id BIGINT UNSIGNED NULL AFTER funnel_stage_id,
    ADD KEY idx_appt_funnel_stage (clinic_id, funnel_stage_id),
    ADD KEY idx_appt_lost_reason (clinic_id, lost_reason_id),
    ADD CONSTRAINT fk_appt_funnel_stage FOREIGN KEY (funnel_stage_id) REFERENCES clinic_funnel_stages (id),
    ADD CONSTRAINT fk_appt_lost_reason FOREIGN KEY (lost_reason_id) REFERENCES clinic_lost_reasons (id);
