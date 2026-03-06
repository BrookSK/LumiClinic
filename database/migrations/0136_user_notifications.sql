-- Migration: 0136_user_notifications
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS user_notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,

    channel VARCHAR(32) NOT NULL DEFAULT 'in_app',
    type VARCHAR(64) NOT NULL,
    title VARCHAR(190) NOT NULL,
    body TEXT NOT NULL,

    reference_type VARCHAR(64) NULL,
    reference_id BIGINT UNSIGNED NULL,

    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_user_notifications_clinic_user (clinic_id, user_id),
    KEY idx_user_notifications_read_at (read_at),
    KEY idx_user_notifications_deleted_at (deleted_at),

    CONSTRAINT fk_user_notifications_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_user_notifications_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
