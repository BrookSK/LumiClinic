-- Migration: 0101_permissions_marketing_calendar
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'marketing_calendar', 'read', 'marketing.calendar.read', 'Ler agenda de marketing', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p
    WHERE p.code = 'marketing.calendar.read'
      AND p.deleted_at IS NULL
);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'marketing_calendar', 'manage', 'marketing.calendar.manage', 'Gerenciar agenda de marketing', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p
    WHERE p.code = 'marketing.calendar.manage'
      AND p.deleted_at IS NULL
);
