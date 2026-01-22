-- Migration: 0026_seed_scheduling_permissions
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Catálogo global de permissões do core de agendamento
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'scheduling', 'read', 'scheduling.read', 'Ver agenda', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='scheduling.read' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'scheduling', 'create', 'scheduling.create', 'Criar agendamento', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='scheduling.create' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'scheduling', 'update', 'scheduling.update', 'Editar agendamento', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='scheduling.update' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'scheduling', 'cancel', 'scheduling.cancel', 'Cancelar agendamento', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='scheduling.cancel' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'scheduling', 'finalize', 'scheduling.finalize', 'Concluir/confirmar atendimento', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='scheduling.finalize' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'professionals', 'manage', 'professionals.manage', 'Gerenciar profissionais', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='professionals.manage' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'services', 'manage', 'services.manage', 'Gerenciar serviços', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='services.manage' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'blocks', 'manage', 'blocks.manage', 'Gerenciar bloqueios de agenda', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='blocks.manage' AND p.deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'schedule_rules', 'manage', 'schedule_rules.manage', 'Gerenciar regras de agenda', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.code='schedule_rules.manage' AND p.deleted_at IS NULL);
