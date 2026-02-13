-- Migration: 0102_rbac_defaults_marketing_calendar
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
  AND p.code IN ('marketing.calendar.read','marketing.calendar.manage')
  AND d.id IS NULL;

-- Admin: permitir por padrão
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'admin'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code IN ('marketing.calendar.read','marketing.calendar.manage')
  AND d.id IS NULL;

-- Reception: permitir apenas leitura por padrão
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'reception', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'reception'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code IN ('marketing.calendar.read')
  AND d.id IS NULL;
