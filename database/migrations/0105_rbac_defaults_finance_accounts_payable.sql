-- Migration: 0105_rbac_defaults_finance_accounts_payable
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Owner: permitir por padrão
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'owner'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code IN ('finance.ap.read','finance.ap.manage')
  AND d.id IS NULL;

-- Admin: permitir por padrão
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'admin'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code IN ('finance.ap.read','finance.ap.manage')
  AND d.id IS NULL;

-- Finance: permitir por padrão
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'finance'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code IN ('finance.ap.read','finance.ap.manage')
  AND d.id IS NULL;

-- Reception: permitir somente leitura
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'reception', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'reception'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code IN ('finance.ap.read')
  AND d.id IS NULL;
