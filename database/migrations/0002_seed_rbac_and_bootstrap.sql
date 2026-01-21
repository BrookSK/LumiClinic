-- Migration: 0002_seed_rbac_and_bootstrap
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

START TRANSACTION;

INSERT INTO clinics (name, status, created_at)
VALUES ('Clínica Demo', 'active', NOW());

SET @clinic_id = LAST_INSERT_ID();

INSERT INTO clinic_settings (clinic_id, timezone, language, created_at)
VALUES (@clinic_id, 'America/Sao_Paulo', 'pt-BR', NOW());

INSERT INTO clinic_terminology (clinic_id, patient_label, appointment_label, professional_label, created_at)
VALUES (@clinic_id, 'Paciente', 'Consulta', 'Profissional', NOW());

INSERT INTO roles (clinic_id, code, name, is_system, created_at)
VALUES
(@clinic_id, 'owner', 'Owner', 1, NOW()),
(@clinic_id, 'admin', 'Admin', 1, NOW()),
(@clinic_id, 'professional', 'Profissional', 1, NOW()),
(@clinic_id, 'reception', 'Recepção', 1, NOW()),
(@clinic_id, 'finance', 'Financeiro', 1, NOW());

SET @role_owner = (SELECT id FROM roles WHERE clinic_id=@clinic_id AND code='owner' LIMIT 1);
SET @role_admin = (SELECT id FROM roles WHERE clinic_id=@clinic_id AND code='admin' LIMIT 1);
SET @role_professional = (SELECT id FROM roles WHERE clinic_id=@clinic_id AND code='professional' LIMIT 1);
SET @role_reception = (SELECT id FROM roles WHERE clinic_id=@clinic_id AND code='reception' LIMIT 1);
SET @role_finance = (SELECT id FROM roles WHERE clinic_id=@clinic_id AND code='finance' LIMIT 1);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
VALUES
(@clinic_id, 'clinics', 'read', 'clinics.read', 'Ver dados da clínica', NOW()),
(@clinic_id, 'clinics', 'create', 'clinics.create', 'Criar clínica', NOW()),
(@clinic_id, 'clinics', 'update', 'clinics.update', 'Editar dados da clínica', NOW()),
(@clinic_id, 'clinics', 'delete', 'clinics.delete', 'Excluir clínica', NOW()),
(@clinic_id, 'clinics', 'export', 'clinics.export', 'Exportar dados da clínica', NOW()),

(@clinic_id, 'users', 'read', 'users.read', 'Listar usuários', NOW()),
(@clinic_id, 'users', 'create', 'users.create', 'Criar usuário', NOW()),
(@clinic_id, 'users', 'update', 'users.update', 'Editar usuário', NOW()),
(@clinic_id, 'users', 'delete', 'users.delete', 'Desativar/excluir usuário', NOW()),
(@clinic_id, 'users', 'export', 'users.export', 'Exportar usuários', NOW()),
(@clinic_id, 'users', 'sensitive', 'users.sensitive', 'Acesso a dados sensíveis de usuários', NOW()),

(@clinic_id, 'settings', 'read', 'settings.read', 'Ver configurações', NOW()),
(@clinic_id, 'settings', 'update', 'settings.update', 'Editar configurações', NOW()),

(@clinic_id, 'audit', 'read', 'audit.read', 'Ver logs de auditoria', NOW()),
(@clinic_id, 'audit', 'export', 'audit.export', 'Exportar logs de auditoria', NOW());

-- Owner/Admin recebem todas as permissões
INSERT INTO role_permissions (clinic_id, role_id, permission_id, created_at)
SELECT @clinic_id, @role_owner, p.id, NOW() FROM permissions p WHERE p.clinic_id=@clinic_id;

INSERT INTO role_permissions (clinic_id, role_id, permission_id, created_at)
SELECT @clinic_id, @role_admin, p.id, NOW() FROM permissions p WHERE p.clinic_id=@clinic_id;

-- Recepção: leitura e cadastro básico de usuários (sem sensitive/export/delete)
INSERT INTO role_permissions (clinic_id, role_id, permission_id, created_at)
SELECT @clinic_id, @role_reception, p.id, NOW()
FROM permissions p
WHERE p.clinic_id=@clinic_id
  AND p.code IN ('users.read', 'users.create', 'users.update');

-- Financeiro: configurações leitura + auditoria leitura (base)
INSERT INTO role_permissions (clinic_id, role_id, permission_id, created_at)
SELECT @clinic_id, @role_finance, p.id, NOW()
FROM permissions p
WHERE p.clinic_id=@clinic_id
  AND p.code IN ('settings.read', 'audit.read');

-- Profissional: (fase 1) apenas leitura de configurações
INSERT INTO role_permissions (clinic_id, role_id, permission_id, created_at)
SELECT @clinic_id, @role_professional, p.id, NOW()
FROM permissions p
WHERE p.clinic_id=@clinic_id
  AND p.code IN ('settings.read');

-- Usuário Owner inicial
-- Email: owner@demo.local
-- Senha: ChangeMe123!
-- password_hash gerado por password_hash() (bcrypt)
INSERT INTO users (clinic_id, name, email, password_hash, status, created_at)
VALUES (@clinic_id, 'Owner Demo', 'owner@demo.local', '$2y$10$K8R7uO5K8xW7XHk7vM9p9e0cK8tZ5m8m7cKQd3Rr8mJQx4S1S2sUu', 'active', NOW());

SET @user_owner = LAST_INSERT_ID();

INSERT INTO user_roles (clinic_id, user_id, role_id, created_at)
VALUES (@clinic_id, @user_owner, @role_owner, NOW());

COMMIT;
