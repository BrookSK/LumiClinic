-- Migration: 0142_rbac_defaults_roles_tuning
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Reception: pode vendas/pagamentos/estoque, mas não pode ver caixa/entries
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'reception', p.code, 'deny', NOW()
FROM permissions p
WHERE p.deleted_at IS NULL
  AND p.code IN (
    'finance.entries.read',
    'finance.entries.create',
    'finance.entries.delete',
    'finance.cashflow.read',
    'finance.cost_centers.manage'
  )
ON DUPLICATE KEY UPDATE effect = VALUES(effect);

-- Professional: ver movimentações e alertas (além de materiais)
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'professional', p.code, 'allow', NOW()
FROM permissions p
WHERE p.deleted_at IS NULL
  AND p.code IN (
    'stock.movements.read',
    'stock.alerts.read'
  )
ON DUPLICATE KEY UPDATE effect = VALUES(effect);
