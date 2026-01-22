-- Migration: 0039_assign_phase3_permissions_owner_admin
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

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
      'patients.read','patients.create','patients.update','patients.export',
      'medical_records.read','medical_records.create','medical_records.update',
      'medical_images.read','medical_images.upload',
      'anamnesis.manage','anamnesis.fill',
      'consent_terms.manage','consent_terms.accept',
      'files.read'
  )
  AND rp.id IS NULL;
