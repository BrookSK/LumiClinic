-- Migration: 0037_seed_phase3_permissions
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Pacientes
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'patients', 'read', 'patients.read', 'Listar/visualizar pacientes', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='patients.read' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'patients', 'create', 'patients.create', 'Criar pacientes', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='patients.create' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'patients', 'update', 'patients.update', 'Editar pacientes', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='patients.update' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'patients', 'export', 'patients.export', 'Exportar dados do paciente', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='patients.export' AND p.deleted_at IS NULL);

-- Prontuário
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'medical_records', 'read', 'medical_records.read', 'Ver prontuário', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='medical_records.read' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'medical_records', 'create', 'medical_records.create', 'Criar registros de prontuário', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='medical_records.create' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'medical_records', 'update', 'medical_records.update', 'Editar registros de prontuário (gera versão)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='medical_records.update' AND p.deleted_at IS NULL);

-- Imagens
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'medical_images', 'read', 'medical_images.read', 'Ver imagens clínicas', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='medical_images.read' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'medical_images', 'upload', 'medical_images.upload', 'Enviar imagens clínicas', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='medical_images.upload' AND p.deleted_at IS NULL);

-- Anamnese
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'anamnesis', 'manage', 'anamnesis.manage', 'Gerenciar templates de anamnese', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='anamnesis.manage' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'anamnesis', 'fill', 'anamnesis.fill', 'Preencher anamnese', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='anamnesis.fill' AND p.deleted_at IS NULL);

-- Termos/assinaturas
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'consent_terms', 'manage', 'consent_terms.manage', 'Gerenciar termos', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='consent_terms.manage' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'consent_terms', 'accept', 'consent_terms.accept', 'Aceitar termos e assinar', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='consent_terms.accept' AND p.deleted_at IS NULL);

-- Arquivos privados
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'files', 'read', 'files.read', 'Baixar arquivos privados (imagens/assinaturas)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='files.read' AND p.deleted_at IS NULL);
