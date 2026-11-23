-- Migration: 0181_patients_delete_permission
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'patients', 'delete', 'patients.delete', 'Excluir pacientes', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='patients.delete' AND p.deleted_at IS NULL);

-- Defaults de RBAC (owner/admin)
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'owner'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code = 'patients.delete'
  AND d.id IS NULL;

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'admin'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code = 'patients.delete'
  AND d.id IS NULL;

-- Clínicas existentes: owner/admin
INSERT INTO role_permissions (clinic_id, role_id, permission_id, effect, created_at)
SELECT r.clinic_id, r.id, p.id, 'allow', NOW()
FROM roles r
INNER JOIN permissions p ON p.deleted_at IS NULL
LEFT JOIN role_permissions rp
       ON rp.clinic_id = r.clinic_id
      AND rp.role_id = r.id
      AND rp.permission_id = p.id
      AND rp.deleted_at IS NULL
WHERE r.deleted_at IS NULL
  AND r.code IN ('owner','admin')
  AND p.code = 'patients.delete'
  AND rp.id IS NULL;
