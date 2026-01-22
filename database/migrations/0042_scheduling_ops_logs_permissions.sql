-- Migration: 0042_scheduling_ops_logs_permissions
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Novas permissões para separar dashboards operacionais e logs imutáveis
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'scheduling', 'ops', 'scheduling.ops', 'Ver painel operacional da agenda', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='scheduling.ops' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'scheduling', 'logs', 'scheduling.logs', 'Ver logs imutáveis de agendamentos', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='scheduling.logs' AND p.deleted_at IS NULL);

-- Defaults por role
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'scheduling.ops', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='scheduling.ops');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'scheduling.logs', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='scheduling.logs');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'scheduling.ops', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='scheduling.ops');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'scheduling.logs', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='scheduling.logs');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'reception', 'scheduling.ops', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='reception' AND d.permission_code='scheduling.ops');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'reception', 'scheduling.logs', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='reception' AND d.permission_code='scheduling.logs');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'professional', 'scheduling.ops', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='professional' AND d.permission_code='scheduling.ops');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'professional', 'scheduling.logs', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='professional' AND d.permission_code='scheduling.logs');

-- Garante que roles owner/admin existentes recebam permissões (idempotente)
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
  AND p.code IN ('scheduling.ops','scheduling.logs')
  AND rp.id IS NULL;
