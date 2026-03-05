-- Migration: 0126_assign_procedures_permissions
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

START TRANSACTION;

-- Garantir que as permissões existam no catálogo global
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'procedures', 'manage', 'procedures.manage', 'Gerenciar procedimentos', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p
    WHERE p.code = 'procedures.manage'
      AND p.deleted_at IS NULL
);

-- Owner/Admin: allow manage
INSERT INTO role_permissions (clinic_id, role_id, permission_id, effect, created_at)
SELECT r.clinic_id, r.id, p.id, 'allow', NOW()
FROM roles r
INNER JOIN permissions p ON p.code IN ('procedures.manage')
WHERE r.deleted_at IS NULL
  AND p.deleted_at IS NULL
  AND r.code IN ('owner', 'admin')
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.clinic_id = r.clinic_id
        AND rp.role_id = r.id
        AND rp.permission_id = p.id
        AND rp.deleted_at IS NULL
  );

COMMIT;
