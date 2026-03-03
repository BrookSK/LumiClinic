-- Migration: 0113_medical_record_template_permissions
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Permission
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'medical_record_templates', 'manage', 'medical_record_templates.manage', 'Gerenciar templates de prontuário', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='medical_record_templates.manage' AND p.deleted_at IS NULL);

-- RBAC defaults (owner/admin)
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', 'medical_record_templates.manage', 'allow', NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM rbac_role_permission_defaults d
  WHERE d.role_code='owner' AND d.permission_code='medical_record_templates.manage'
);

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', 'medical_record_templates.manage', 'allow', NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM rbac_role_permission_defaults d
  WHERE d.role_code='admin' AND d.permission_code='medical_record_templates.manage'
);
