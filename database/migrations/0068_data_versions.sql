-- Migration: 0068_data_versions
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Versionamento imutável (snapshot BEFORE) para dados sensíveis.
CREATE TABLE IF NOT EXISTS data_versions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    clinic_id BIGINT UNSIGNED NOT NULL,

    entity_type VARCHAR(64) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,

    action VARCHAR(128) NOT NULL,

    snapshot_json JSON NOT NULL,
    snapshot_hash CHAR(64) NOT NULL,

    created_by_user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,

    occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_dv_entity (clinic_id, entity_type, entity_id),
    KEY idx_dv_action (clinic_id, action),
    KEY idx_dv_occurred (occurred_at),
    KEY idx_dv_user (clinic_id, created_by_user_id),
    UNIQUE KEY uq_dv_hash (snapshot_hash),

    CONSTRAINT fk_dv_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_dv_user FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
