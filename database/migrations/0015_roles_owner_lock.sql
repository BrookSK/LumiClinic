-- Migration: 0015_roles_owner_lock
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Enterprise-grade: role Owner travado (não editável)
ALTER TABLE roles
    ADD COLUMN is_editable TINYINT(1) NOT NULL DEFAULT 1 AFTER is_system;

UPDATE roles
SET is_editable = 0
WHERE code = 'owner'
  AND deleted_at IS NULL;

CREATE INDEX idx_roles_is_editable ON roles (is_editable);
