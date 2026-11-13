-- Migration: 0174_owner_scheduling_logs_access
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.
-- Garante (de forma idempotente) que o papel "owner" tenha acesso aos logs/painel operacional da agenda.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Garante que as permissões existam no catálogo global
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'scheduling', 'ops', 'scheduling.ops', 'Ver painel operacional da agenda', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='scheduling.ops' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'scheduling', 'logs', 'scheduling.logs', 'Ver logs imutáveis de agendamentos', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='scheduling.logs' AND p.deleted_at IS NULL);

-- Default do papel owner: allow (corrige eventual deny e cria se faltar)
UPDATE rbac_role_permission_defaults
   SET effect = 'allow'
 WHERE role_code = 'owner'
   AND permission_code IN ('scheduling.ops','scheduling.logs')
   AND effect <> 'allow';

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', c.code, 'allow', NOW()
FROM (SELECT 'scheduling.ops' AS code UNION ALL SELECT 'scheduling.logs') c
WHERE NOT EXISTS (
    SELECT 1 FROM rbac_role_permission_defaults d
    WHERE d.role_code='owner' AND d.permission_code=c.code
);

-- Reativa / corrige vínculos já existentes dos owners
UPDATE role_permissions rp
JOIN roles r ON r.id = rp.role_id AND r.deleted_at IS NULL
JOIN permissions p ON p.id = rp.permission_id AND p.deleted_at IS NULL
   SET rp.deleted_at = NULL,
       rp.effect = 'allow',
       rp.updated_at = NOW()
 WHERE r.code = 'owner'
   AND p.code IN ('scheduling.ops','scheduling.logs');

-- Cria vínculos que faltam para todos os owners existentes
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
  AND r.clinic_id IS NOT NULL
  AND r.code = 'owner'
  AND p.code IN ('scheduling.ops','scheduling.logs')
  AND rp.id IS NULL;
