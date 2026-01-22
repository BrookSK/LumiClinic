-- Migration: 0017_assign_rbac_manage_to_owner_roles
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Garante que a permissão global exista
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'rbac', 'manage', 'rbac.manage', 'Gerenciar papéis e permissões', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p WHERE p.code = 'rbac.manage' AND p.deleted_at IS NULL
);

-- Atribui rbac.manage para todo role owner de todas as clínicas (idempotente)
INSERT INTO role_permissions (clinic_id, role_id, permission_id, effect, created_at)
SELECT r.clinic_id, r.id, p.id, 'allow', NOW()
FROM roles r
CROSS JOIN permissions p
LEFT JOIN role_permissions rp
       ON rp.clinic_id = r.clinic_id
      AND rp.role_id = r.id
      AND rp.permission_id = p.id
WHERE r.code = 'owner'
  AND r.deleted_at IS NULL
  AND p.code = 'rbac.manage'
  AND p.deleted_at IS NULL
  AND rp.id IS NULL;
