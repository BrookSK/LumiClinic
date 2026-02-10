-- Migration: 0085_system_error_logs
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS system_error_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    status_code INT NOT NULL,
    error_type VARCHAR(64) NOT NULL,
    message VARCHAR(1024) NOT NULL,

    method VARCHAR(16) NOT NULL,
    path VARCHAR(2048) NOT NULL,

    clinic_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    is_super_admin TINYINT(1) NOT NULL DEFAULT 0,

    ip VARCHAR(64) NULL,
    user_agent VARCHAR(512) NULL,

    trace_text MEDIUMTEXT NULL,
    context_json MEDIUMTEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_sys_err_status_created (status_code, created_at),
    KEY idx_sys_err_clinic_created (clinic_id, created_at),
    KEY idx_sys_err_user_created (user_id, created_at),

    CONSTRAINT fk_sys_err_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_sys_err_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
