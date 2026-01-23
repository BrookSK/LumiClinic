-- Migration: 0074_queue_jobs
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS queue_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NULL,

    queue VARCHAR(64) NOT NULL DEFAULT 'default',
    job_type VARCHAR(100) NOT NULL,
    payload_json JSON NOT NULL,

    status VARCHAR(16) NOT NULL DEFAULT 'pending',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts INT UNSIGNED NOT NULL DEFAULT 10,

    run_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    locked_at DATETIME NULL,
    locked_by VARCHAR(64) NULL,

    last_error TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_qj_status_run (status, run_at, id),
    KEY idx_qj_queue_status_run (queue, status, run_at, id),
    KEY idx_qj_locked (locked_at, id),
    KEY idx_qj_clinic (clinic_id, id),

    CONSTRAINT fk_qj_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
