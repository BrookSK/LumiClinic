-- Migration: 0100_marketing_calendar_entries
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS marketing_calendar_entries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    entry_date DATE NOT NULL,
    content_type VARCHAR(64) NOT NULL DEFAULT 'post',
    status VARCHAR(32) NOT NULL DEFAULT 'planned',
    title VARCHAR(140) NOT NULL,
    notes TEXT NULL,

    assigned_user_id BIGINT UNSIGNED NULL,
    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_marketing_entries_date (clinic_id, entry_date),
    KEY idx_marketing_entries_status (clinic_id, status, entry_date),
    KEY idx_marketing_entries_assigned (clinic_id, assigned_user_id, entry_date),
    KEY idx_marketing_entries_deleted_at (deleted_at),

    CONSTRAINT fk_marketing_entries_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_marketing_entries_assigned_user FOREIGN KEY (assigned_user_id) REFERENCES users (id),
    CONSTRAINT fk_marketing_entries_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
