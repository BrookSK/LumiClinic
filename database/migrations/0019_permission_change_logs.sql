-- Migration: 0019_permission_change_logs
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS permission_change_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    clinic_id BIGINT UNSIGNED NOT NULL,
    actor_user_id BIGINT UNSIGNED NOT NULL,

    role_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(64) NOT NULL,

    before_json JSON NULL,
    after_json JSON NULL,

    ip_address VARCHAR(64) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_pcl_clinic_id (clinic_id),
    KEY idx_pcl_actor_user_id (actor_user_id),
    KEY idx_pcl_role_id (role_id),
    KEY idx_pcl_action (action),
    KEY idx_pcl_created_at (created_at),

    CONSTRAINT fk_pcl_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_pcl_actor_user FOREIGN KEY (actor_user_id) REFERENCES users (id),
    CONSTRAINT fk_pcl_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
