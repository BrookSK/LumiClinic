-- Migration: 0045_finance_permissions
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance', 'sales_read', 'finance.sales.read', 'Ver vendas', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.sales.read' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance', 'sales_create', 'finance.sales.create', 'Criar vendas', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.sales.create' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance', 'sales_update', 'finance.sales.update', 'Editar vendas (itens/valores)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.sales.update' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance', 'sales_cancel', 'finance.sales.cancel', 'Cancelar vendas', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.sales.cancel' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance', 'payments_read', 'finance.payments.read', 'Ver pagamentos', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.payments.read' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance', 'payments_create', 'finance.payments.create', 'Registrar pagamentos', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.payments.create' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance', 'payments_refund', 'finance.payments.refund', 'Estornar pagamentos', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.payments.refund' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance', 'cashflow_read', 'finance.cashflow.read', 'Ver fluxo de caixa', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.cashflow.read' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance', 'reports_read', 'finance.reports.read', 'Ver relatórios financeiros', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.reports.read' AND p.deleted_at IS NULL);

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'finance.sales.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='finance.sales.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'finance.sales.create', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='finance.sales.create');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'finance.sales.update', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='finance.sales.update');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'finance.sales.cancel', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='finance.sales.cancel');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'finance.payments.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='finance.payments.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'finance.payments.create', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='finance.payments.create');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'finance.payments.refund', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='finance.payments.refund');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'finance.cashflow.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='finance.cashflow.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'finance.reports.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code='finance.reports.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'finance.sales.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='finance.sales.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'finance.sales.create', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='finance.sales.create');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'finance.sales.update', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='finance.sales.update');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'finance.sales.cancel', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='finance.sales.cancel');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'finance.payments.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='finance.payments.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'finance.payments.create', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='finance.payments.create');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'finance.payments.refund', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='finance.payments.refund');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'finance.cashflow.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='finance.cashflow.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'finance.reports.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code='finance.reports.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', 'finance.sales.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code='finance.sales.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', 'finance.sales.create', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code='finance.sales.create');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', 'finance.sales.update', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code='finance.sales.update');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', 'finance.sales.cancel', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code='finance.sales.cancel');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', 'finance.payments.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code='finance.payments.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', 'finance.payments.create', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code='finance.payments.create');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', 'finance.payments.refund', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code='finance.payments.refund');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', 'finance.cashflow.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code='finance.cashflow.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', 'finance.reports.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code='finance.reports.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'reception', 'finance.sales.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='reception' AND d.permission_code='finance.sales.read');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'reception', 'finance.sales.create', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='reception' AND d.permission_code='finance.sales.create');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'reception', 'finance.payments.create', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='reception' AND d.permission_code='finance.payments.create');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'professional', 'finance.sales.read', 'allow', NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='professional' AND d.permission_code='finance.sales.read');

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
  AND r.code IN ('owner','admin','finance','reception')
  AND p.code IN (
        'finance.sales.read','finance.sales.create','finance.sales.update','finance.sales.cancel',
        'finance.payments.read','finance.payments.create','finance.payments.refund',
        'finance.cashflow.read','finance.reports.read'
  )
  AND rp.id IS NULL;
