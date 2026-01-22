-- Migration: 0024_scheduling_blocks
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS scheduling_blocks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED NULL,

    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,

    reason VARCHAR(255) NULL,
    type VARCHAR(32) NOT NULL DEFAULT 'manual',

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_sb_clinic_id (clinic_id),
    KEY idx_sb_professional_id (professional_id),
    KEY idx_sb_start_end (start_at, end_at),
    KEY idx_sb_deleted_at (deleted_at),
    CONSTRAINT fk_sb_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_sb_professional FOREIGN KEY (professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_sb_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
