-- Migration: 0127_procedure_performed
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS procedure_performed (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    appointment_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NULL,
    professional_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    procedure_id BIGINT UNSIGNED NULL,

    real_started_at DATETIME NULL,
    real_ended_at DATETIME NULL,
    real_duration_minutes SMALLINT UNSIGNED NULL,

    stock_total_cost_snapshot DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stock_movement_ids_json JSON NULL,

    financial_entry_id BIGINT UNSIGNED NULL,

    note VARCHAR(255) NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_proc_perf_clinic_appt (clinic_id, appointment_id),
    KEY idx_proc_perf_clinic (clinic_id, id),
    KEY idx_proc_perf_appt (appointment_id),
    KEY idx_proc_perf_procedure (procedure_id),
    KEY idx_proc_perf_deleted_at (deleted_at),

    CONSTRAINT fk_proc_perf_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_proc_perf_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id),
    CONSTRAINT fk_proc_perf_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_proc_perf_professional FOREIGN KEY (professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_proc_perf_service FOREIGN KEY (service_id) REFERENCES services (id),
    CONSTRAINT fk_proc_perf_procedure FOREIGN KEY (procedure_id) REFERENCES procedures (id),
    CONSTRAINT fk_proc_perf_fin_entry FOREIGN KEY (financial_entry_id) REFERENCES financial_entries (id),
    CONSTRAINT fk_proc_perf_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procedure_performed_materials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    performed_id BIGINT UNSIGNED NOT NULL,
    material_id BIGINT UNSIGNED NOT NULL,

    quantity DECIMAL(12,3) NOT NULL,
    note VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_ppm_clinic_perf (clinic_id, performed_id),
    KEY idx_ppm_clinic_material (clinic_id, material_id),
    KEY idx_ppm_deleted_at (deleted_at),

    CONSTRAINT fk_ppm_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_ppm_perf FOREIGN KEY (performed_id) REFERENCES procedure_performed (id),
    CONSTRAINT fk_ppm_material FOREIGN KEY (material_id) REFERENCES materials (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
