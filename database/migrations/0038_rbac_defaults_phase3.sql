-- Migration: 0038_rbac_defaults_phase3
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Owner/Admin: tudo de pacientes + prontuário + imagens + anamnese + termos
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'owner'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code IN (
      'patients.read','patients.create','patients.update','patients.export',
      'medical_records.read','medical_records.create','medical_records.update',
      'medical_images.read','medical_images.upload',
      'anamnesis.manage','anamnesis.fill',
      'consent_terms.manage','consent_terms.accept',
      'files.read'
  )
  AND d.id IS NULL;

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'admin'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code IN (
      'patients.read','patients.create','patients.update','patients.export',
      'medical_records.read','medical_records.create','medical_records.update',
      'medical_images.read','medical_images.upload',
      'anamnesis.manage','anamnesis.fill',
      'consent_terms.manage','consent_terms.accept',
      'files.read'
  )
  AND d.id IS NULL;

-- Reception: pacientes + leitura prontuário + preencher anamnese + aceitar termos + download
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'reception', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'reception'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code IN (
      'patients.read','patients.create','patients.update',
      'medical_records.read',
      'anamnesis.fill',
      'consent_terms.accept',
      'files.read'
  )
  AND d.id IS NULL;

-- Professional: leitura prontuário + criar/editar registro + imagens + anamnese + termos + download
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'professional', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'professional'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code IN (
      'patients.read',
      'medical_records.read','medical_records.create','medical_records.update',
      'medical_images.read','medical_images.upload',
      'anamnesis.fill',
      'consent_terms.accept',
      'files.read'
  )
  AND d.id IS NULL;
