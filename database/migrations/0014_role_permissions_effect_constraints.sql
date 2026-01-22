-- Migration: 0014_role_permissions_effect_constraints
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- allow/deny com deny ganhando (computed no backend)
ALTER TABLE role_permissions
    ADD COLUMN effect ENUM('allow','deny') NOT NULL DEFAULT 'allow' AFTER permission_id;

-- Preparado para ACL/field-level no futuro
ALTER TABLE role_permissions
    ADD COLUMN constraints_json JSON NULL AFTER effect;

CREATE INDEX idx_role_permissions_effect ON role_permissions (effect);
