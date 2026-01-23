-- Migration: 0049_finance_cashflow_reports_permissions
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Novas permissões para caixa e relatórios (catálogo global)
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance', 'entries_read', 'finance.entries.read', 'Ver lançamentos financeiros', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.entries.read' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance', 'entries_create', 'finance.entries.create', 'Criar lançamentos financeiros', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.entries.create' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance', 'entries_delete', 'finance.entries.delete', 'Excluir/estornar lançamentos (soft delete)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.entries.delete' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance', 'cost_centers_manage', 'finance.cost_centers.manage', 'Gerenciar centros de custo', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.cost_centers.manage' AND p.deleted_at IS NULL);

-- Defaults por role
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'finance.entries.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='finance.entries.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'finance.entries.create', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='finance.entries.create');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'finance.entries.delete', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='finance.entries.delete');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'finance.cost_centers.manage', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='finance.cost_centers.manage');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'finance.entries.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='finance.entries.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'finance.entries.create', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='finance.entries.create');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'finance.entries.delete', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='finance.entries.delete');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'finance.cost_centers.manage', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='finance.cost_centers.manage');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', 'finance.entries.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code='finance.entries.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', 'finance.entries.create', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code='finance.entries.create');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', 'finance.entries.delete', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code='finance.entries.delete');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', 'finance.cost_centers.manage', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code='finance.cost_centers.manage');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'reception', 'finance.entries.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='reception' AND d.permission_code='finance.entries.read');

-- Nota: professional permanece leitura limitada via finance.sales.read (restrição aplicada no controller)

-- Garante que roles existentes recebam permissões (idempotente)
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
  AND r.code IN ('owner','admin','finance')
  AND p.code IN ('finance.entries.read','finance.entries.create','finance.entries.delete','finance.cost_centers.manage')
  AND rp.id IS NULL;
