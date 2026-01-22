-- Migration: 0028_assign_scheduling_permissions_to_owner_admin
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Garante que roles owner/admin tenham permissões do core de agendamento (idempotente)
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
  AND p.code IN (
      'scheduling.read',
      'scheduling.create',
      'scheduling.update',
      'scheduling.cancel',
      'scheduling.finalize',
      'professionals.manage',
      'services.manage',
      'blocks.manage',
      'schedule_rules.manage'
  )
  AND rp.id IS NULL;
