-- Migration: 0104_permissions_finance_accounts_payable
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance_ap', 'read', 'finance.ap.read', 'Ler contas a pagar', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p
    WHERE p.code = 'finance.ap.read'
      AND p.deleted_at IS NULL
);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'finance_ap', 'manage', 'finance.ap.manage', 'Gerenciar contas a pagar', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p
    WHERE p.code = 'finance.ap.manage'
      AND p.deleted_at IS NULL
);
