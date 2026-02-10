-- Migration: 0083_owner_role_permissions_sync
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Reativa permissões soft-deletadas do Owner (caso existam)
UPDATE role_permissions rp
JOIN roles r ON r.id = rp.role_id AND r.deleted_at IS NULL
JOIN permissions p ON p.id = rp.permission_id AND p.deleted_at IS NULL
SET rp.deleted_at = NULL,
    rp.updated_at = NOW()
WHERE r.code = 'owner'
  AND rp.deleted_at IS NOT NULL;

-- Garante que o Owner tenha TODAS as permissões (globais e futuras)
INSERT INTO role_permissions (clinic_id, role_id, permission_id, created_at)
SELECT r.clinic_id, r.id, p.id, NOW()
FROM roles r
CROSS JOIN permissions p
WHERE r.code = 'owner'
  AND r.deleted_at IS NULL
  AND r.clinic_id IS NOT NULL
  AND p.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.clinic_id = r.clinic_id
        AND rp.role_id = r.id
        AND rp.permission_id = p.id
        AND rp.deleted_at IS NULL
  );
