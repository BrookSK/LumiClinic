-- Migration: 0064_user_permissions_override
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Overrides por usuário (allow/deny) para complementar o RBAC padrão.
-- Preparado para constraints_json (field-level / ABAC future-proof).
CREATE TABLE IF NOT EXISTS user_permissions_override (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,

    effect ENUM('allow','deny') NOT NULL DEFAULT 'allow',
    constraints_json JSON NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_upo_clinic_user (clinic_id, user_id),
    KEY idx_upo_permission (permission_id),
    KEY idx_upo_effect (effect),
    KEY idx_upo_deleted_at (deleted_at),

    CONSTRAINT fk_upo_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_upo_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_upo_permission FOREIGN KEY (permission_id) REFERENCES permissions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
