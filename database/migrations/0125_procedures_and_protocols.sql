-- Migration: 0125_procedures_and_protocols
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS procedures (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(190) NOT NULL,
    contraindications TEXT NULL,
    pre_guidelines TEXT NULL,
    post_guidelines TEXT NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_procedures_clinic_id (clinic_id),
    KEY idx_procedures_status (status),
    KEY idx_procedures_deleted_at (deleted_at),
    CONSTRAINT fk_procedures_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procedure_protocols (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    procedure_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(190) NOT NULL,
    notes TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_proc_protocols_clinic (clinic_id),
    KEY idx_proc_protocols_procedure (procedure_id),
    KEY idx_proc_protocols_deleted_at (deleted_at),
    CONSTRAINT fk_proc_protocols_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_proc_protocols_procedure FOREIGN KEY (procedure_id) REFERENCES procedures (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procedure_protocol_steps (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    protocol_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(190) NOT NULL,
    duration_minutes SMALLINT UNSIGNED NULL,
    notes TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_proc_steps_clinic (clinic_id),
    KEY idx_proc_steps_protocol (protocol_id),
    KEY idx_proc_steps_deleted_at (deleted_at),
    CONSTRAINT fk_proc_steps_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_proc_steps_protocol FOREIGN KEY (protocol_id) REFERENCES procedure_protocols (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE services
    ADD COLUMN procedure_id BIGINT UNSIGNED NULL AFTER name,
    ADD KEY idx_services_procedure_id (procedure_id),
    ADD CONSTRAINT fk_services_procedure FOREIGN KEY (procedure_id) REFERENCES procedures (id);

COMMIT;
