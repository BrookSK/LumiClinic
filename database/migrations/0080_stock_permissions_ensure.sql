-- Migration: 0080_stock_permissions_ensure
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Garante que o catálogo global de permissões de estoque exista
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'stock', 'materials_read', 'stock.materials.read', 'Ver materiais/estoque', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='stock.materials.read' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'stock', 'materials_manage', 'stock.materials.manage', 'Criar/editar materiais', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='stock.materials.manage' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'stock', 'movements_read', 'stock.movements.read', 'Ver movimentações de estoque', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='stock.movements.read' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'stock', 'movements_create', 'stock.movements.create', 'Criar movimentações de estoque', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='stock.movements.create' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'stock', 'alerts_read', 'stock.alerts.read', 'Ver alertas de estoque', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='stock.alerts.read' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'stock', 'reports_read', 'stock.reports.read', 'Ver relatórios de estoque', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='stock.reports.read' AND p.deleted_at IS NULL);

-- Garante defaults por role (para resetar padrão funcionar)
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'stock.materials.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='stock.materials.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'stock.materials.manage', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='stock.materials.manage');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'stock.materials.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='stock.materials.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'stock.materials.manage', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='stock.materials.manage');

-- Atribui para roles existentes (idempotente)
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
  AND p.code IN ('stock.materials.read','stock.materials.manage')
  AND rp.id IS NULL;
