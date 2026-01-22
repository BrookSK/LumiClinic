-- Migration: 0016_seed_global_permissions_rbac
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Permissão global para gestão de papéis/permissões (RBAC)
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'rbac', 'manage', 'rbac.manage', 'Gerenciar papéis e permissões', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p WHERE p.code = 'rbac.manage' AND p.deleted_at IS NULL
);
