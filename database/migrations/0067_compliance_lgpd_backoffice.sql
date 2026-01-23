-- Migration: 0067_compliance_lgpd_backoffice
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Permissões (catálogo global)
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'compliance', 'read', 'compliance.lgpd.read', 'Ver solicitações LGPD', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'compliance.lgpd.read');

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'compliance', 'process', 'compliance.lgpd.process', 'Processar solicitações LGPD', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'compliance.lgpd.process');

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'compliance', 'export', 'compliance.lgpd.export', 'Exportar dados do titular (LGPD)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'compliance.lgpd.export');

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'compliance', 'anonymize', 'compliance.lgpd.anonymize', 'Anonimizar/excluir dados do titular (LGPD)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'compliance.lgpd.anonymize');

-- Defaults (roles system)
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', p.code, 'allow', NOW()
FROM permissions p
WHERE p.code IN (
    'compliance.lgpd.read',
    'compliance.lgpd.process',
    'compliance.lgpd.export',
    'compliance.lgpd.anonymize'
)
  AND NOT EXISTS (
    SELECT 1 FROM rbac_role_permission_defaults d
    WHERE d.role_code = 'owner' AND d.permission_code = p.code
  );

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', p.code, 'allow', NOW()
FROM permissions p
WHERE p.code IN (
    'compliance.lgpd.read',
    'compliance.lgpd.process',
    'compliance.lgpd.export',
    'compliance.lgpd.anonymize'
)
  AND NOT EXISTS (
    SELECT 1 FROM rbac_role_permission_defaults d
    WHERE d.role_code = 'admin' AND d.permission_code = p.code
  );
