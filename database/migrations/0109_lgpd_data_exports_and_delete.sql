-- Migration: 0109_lgpd_data_exports_and_delete
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS data_exports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,

    action VARCHAR(128) NOT NULL,
    entity_type VARCHAR(64) NULL,
    entity_id BIGINT UNSIGNED NULL,

    format VARCHAR(32) NULL,
    filename VARCHAR(190) NULL,

    meta_json JSON NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_data_exports_clinic (clinic_id, id),
    KEY idx_data_exports_user (user_id, id),
    KEY idx_data_exports_action (action, id),
    KEY idx_data_exports_entity (entity_type, entity_id),

    CONSTRAINT fk_data_exports_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_data_exports_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
