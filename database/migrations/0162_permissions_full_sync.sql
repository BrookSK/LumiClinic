-- Migration: 0162_permissions_full_sync
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.
-- Garante que TODAS as permissões usadas no sistema existam no catálogo
-- e que o Owner tenha acesso total.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ═══════════════════════════════════════════════════════════════
-- 1) Garantir que todas as permissões existam no catálogo global
-- ═══════════════════════════════════════════════════════════════

-- Dashboard
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'dashboard','read','dashboard.read','Ver dashboard',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='dashboard.read' AND deleted_at IS NULL);

-- Pacientes
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'patients','read','patients.read','Ver pacientes',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='patients.read' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'patients','create','patients.create','Cadastrar pacientes',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='patients.create' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'patients','update','patients.update','Editar pacientes',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='patients.update' AND deleted_at IS NULL);

-- Agendamento
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'scheduling','read','scheduling.read','Ver agenda',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='scheduling.read' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'scheduling','create','scheduling.create','Criar agendamentos',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='scheduling.create' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'scheduling','update','scheduling.update','Editar agendamentos',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='scheduling.update' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'scheduling','cancel','scheduling.cancel','Cancelar agendamentos',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='scheduling.cancel' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'scheduling','ops','scheduling.ops','Operação da agenda',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='scheduling.ops' AND deleted_at IS NULL);

-- Prontuário
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'medical_records','read','medical_records.read','Ver prontuários',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='medical_records.read' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'medical_records','create','medical_records.create','Criar registros no prontuário',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='medical_records.create' AND deleted_at IS NULL);

-- Imagens médicas
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'medical_images','read','medical_images.read','Ver imagens clínicas',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='medical_images.read' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'medical_images','upload','medical_images.upload','Enviar/anotar imagens',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='medical_images.upload' AND deleted_at IS NULL);

-- Financeiro - Vendas/Orçamentos
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'finance','sales_read','finance.sales.read','Ver orçamentos/vendas',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='finance.sales.read' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'finance','sales_create','finance.sales.create','Criar orçamentos',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='finance.sales.create' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'finance','sales_update','finance.sales.update','Editar orçamentos',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='finance.sales.update' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'finance','sales_cancel','finance.sales.cancel','Cancelar orçamentos',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='finance.sales.cancel' AND deleted_at IS NULL);

-- Financeiro - Pagamentos
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'finance','payments_create','finance.payments.create','Registrar pagamentos',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='finance.payments.create' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'finance','payments_refund','finance.payments.refund','Estornar pagamentos',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='finance.payments.refund' AND deleted_at IS NULL);

-- Financeiro - Relatórios
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'finance','reports_read','finance.reports.read','Ver relatórios financeiros',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='finance.reports.read' AND deleted_at IS NULL);

-- Serviços e Procedimentos
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'services','manage','services.manage','Gerenciar serviços',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='services.manage' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'procedures','manage','procedures.manage','Gerenciar protocolos clínicos',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='procedures.manage' AND deleted_at IS NULL);

-- Anamnese
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'anamnesis','manage','anamnesis.manage','Gerenciar templates de anamnese',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='anamnesis.manage' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'anamnesis','fill','anamnesis.fill','Preencher/enviar anamnese',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='anamnesis.fill' AND deleted_at IS NULL);

-- Termos de consentimento
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'consent','manage','consent_terms.manage','Gerenciar termos de consentimento',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='consent_terms.manage' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'consent','accept','consent_terms.accept','Aceitar/coletar termos',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='consent_terms.accept' AND deleted_at IS NULL);

-- Configurações
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'settings','read','settings.read','Ver configurações',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='settings.read' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'settings','update','settings.update','Alterar configurações',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='settings.update' AND deleted_at IS NULL);

-- Clínica
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'clinics','read','clinics.read','Ver dados da clínica',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='clinics.read' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'clinics','update','clinics.update','Editar dados da clínica',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='clinics.update' AND deleted_at IS NULL);

-- Usuários e RBAC
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'users','manage','users.manage','Gerenciar usuários',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='users.manage' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'rbac','manage','rbac.manage','Gerenciar papéis e permissões',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='rbac.manage' AND deleted_at IS NULL);

-- Regras de agenda
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'schedule_rules','manage','schedule_rules.manage','Gerenciar regras de agenda',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='schedule_rules.manage' AND deleted_at IS NULL);

-- Arquivos
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'files','read','files.read','Acessar arquivos',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='files.read' AND deleted_at IS NULL);

-- Auditoria
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'audit','read','audit.read','Ver logs de auditoria',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='audit.read' AND deleted_at IS NULL);

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'audit','export','audit.export','Exportar logs de auditoria',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='audit.export' AND deleted_at IS NULL);

-- Templates de prontuário
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL,'medical_record_templates','manage','medical_record_templates.manage','Gerenciar templates de prontuário',NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code='medical_record_templates.manage' AND deleted_at IS NULL);

-- ═══════════════════════════════════════════════════════════════
-- 2) OWNER: garantir acesso a TODAS as permissões
-- ═══════════════════════════════════════════════════════════════

-- Reativar permissões soft-deletadas do Owner
UPDATE role_permissions rp
JOIN roles r ON r.id = rp.role_id AND r.deleted_at IS NULL
JOIN permissions p ON p.id = rp.permission_id AND p.deleted_at IS NULL
SET rp.deleted_at = NULL, rp.updated_at = NOW()
WHERE r.code = 'owner' AND rp.deleted_at IS NOT NULL;

-- Inserir permissões faltantes para Owner
INSERT INTO role_permissions (clinic_id, role_id, permission_id, effect, created_at)
SELECT r.clinic_id, r.id, p.id, 'allow', NOW()
FROM roles r
CROSS JOIN permissions p
WHERE r.code = 'owner'
  AND r.deleted_at IS NULL
  AND r.clinic_id IS NOT NULL
  AND p.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.clinic_id = r.clinic_id AND rp.role_id = r.id AND rp.permission_id = p.id
  );

-- ═══════════════════════════════════════════════════════════════
-- 3) ADMIN: garantir acesso a quase tudo (exceto rbac.manage)
-- ═══════════════════════════════════════════════════════════════

INSERT INTO role_permissions (clinic_id, role_id, permission_id, effect, created_at)
SELECT r.clinic_id, r.id, p.id, 'allow', NOW()
FROM roles r
CROSS JOIN permissions p
WHERE r.code = 'admin'
  AND r.deleted_at IS NULL
  AND r.clinic_id IS NOT NULL
  AND p.deleted_at IS NULL
  AND p.code NOT IN ('rbac.manage')
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.clinic_id = r.clinic_id AND rp.role_id = r.id AND rp.permission_id = p.id
  );

-- ═══════════════════════════════════════════════════════════════
-- 4) RECEPÇÃO: leitura + cadastro básico + agenda + anamnese
-- ═══════════════════════════════════════════════════════════════

INSERT INTO role_permissions (clinic_id, role_id, permission_id, effect, created_at)
SELECT r.clinic_id, r.id, p.id, 'allow', NOW()
FROM roles r
INNER JOIN permissions p ON p.deleted_at IS NULL
WHERE r.code = 'reception'
  AND r.deleted_at IS NULL
  AND r.clinic_id IS NOT NULL
  AND p.code IN (
      'dashboard.read',
      'patients.read', 'patients.create', 'patients.update',
      'scheduling.read', 'scheduling.create', 'scheduling.update', 'scheduling.cancel', 'scheduling.ops',
      'medical_records.read',
      'medical_images.read',
      'finance.sales.read', 'finance.sales.create', 'finance.sales.update',
      'finance.payments.create',
      'anamnesis.fill',
      'consent_terms.accept',
      'settings.read',
      'clinics.read',
      'files.read',
      'marketing.calendar.read'
  )
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.clinic_id = r.clinic_id AND rp.role_id = r.id AND rp.permission_id = p.id
  );

-- ═══════════════════════════════════════════════════════════════
-- 5) PROFISSIONAL: leitura + prontuário + imagens + anamnese
-- ═══════════════════════════════════════════════════════════════

INSERT INTO role_permissions (clinic_id, role_id, permission_id, effect, created_at)
SELECT r.clinic_id, r.id, p.id, 'allow', NOW()
FROM roles r
INNER JOIN permissions p ON p.deleted_at IS NULL
WHERE r.code = 'professional'
  AND r.deleted_at IS NULL
  AND r.clinic_id IS NOT NULL
  AND p.code IN (
      'dashboard.read',
      'patients.read',
      'scheduling.read',
      'medical_records.read', 'medical_records.create',
      'medical_images.read', 'medical_images.upload',
      'medical_record_templates.manage',
      'anamnesis.fill', 'anamnesis.manage',
      'consent_terms.accept',
      'finance.sales.read',
      'files.read',
      'procedures.manage'
  )
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.clinic_id = r.clinic_id AND rp.role_id = r.id AND rp.permission_id = p.id
  );

-- ═══════════════════════════════════════════════════════════════
-- 6) FINANCEIRO: tudo de finanças + leitura básica
-- ═══════════════════════════════════════════════════════════════

INSERT INTO role_permissions (clinic_id, role_id, permission_id, effect, created_at)
SELECT r.clinic_id, r.id, p.id, 'allow', NOW()
FROM roles r
INNER JOIN permissions p ON p.deleted_at IS NULL
WHERE r.code = 'finance'
  AND r.deleted_at IS NULL
  AND r.clinic_id IS NOT NULL
  AND p.code IN (
      'dashboard.read',
      'patients.read',
      'scheduling.read',
      'finance.sales.read', 'finance.sales.create', 'finance.sales.update', 'finance.sales.cancel',
      'finance.payments.create', 'finance.payments.refund',
      'finance.entries.read', 'finance.entries.create', 'finance.entries.delete',
      'finance.cost_centers.manage',
      'finance.reports.read',
      'finance.ap.read', 'finance.ap.manage',
      'settings.read',
      'files.read',
      'audit.read'
  )
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.clinic_id = r.clinic_id AND rp.role_id = r.id AND rp.permission_id = p.id
  );

-- ═══════════════════════════════════════════════════════════════
-- 7) RBAC DEFAULTS para novas clínicas
-- ═══════════════════════════════════════════════════════════════

-- Owner: TODAS as permissões
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', p.code, 'allow', NOW()
FROM permissions p
WHERE p.deleted_at IS NULL
  AND NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code=p.code);

-- Admin: tudo exceto rbac.manage
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', p.code, 'allow', NOW()
FROM permissions p
WHERE p.deleted_at IS NULL
  AND p.code <> 'rbac.manage'
  AND NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code=p.code);

-- Reception: defaults
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'reception', p.code, 'allow', NOW()
FROM permissions p
WHERE p.deleted_at IS NULL
  AND p.code IN (
      'dashboard.read','patients.read','patients.create','patients.update',
      'scheduling.read','scheduling.create','scheduling.update','scheduling.cancel','scheduling.ops',
      'medical_records.read','medical_images.read',
      'finance.sales.read','finance.sales.create','finance.sales.update',
      'finance.payments.create',
      'anamnesis.fill','consent_terms.accept',
      'settings.read','clinics.read','files.read','marketing.calendar.read'
  )
  AND NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='reception' AND d.permission_code=p.code);

-- Professional: defaults
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'professional', p.code, 'allow', NOW()
FROM permissions p
WHERE p.deleted_at IS NULL
  AND p.code IN (
      'dashboard.read','patients.read',
      'scheduling.read',
      'medical_records.read','medical_records.create',
      'medical_images.read','medical_images.upload',
      'medical_record_templates.manage',
      'anamnesis.fill','anamnesis.manage',
      'consent_terms.accept',
      'finance.sales.read',
      'files.read','procedures.manage'
  )
  AND NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='professional' AND d.permission_code=p.code);

-- Finance: defaults
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', p.code, 'allow', NOW()
FROM permissions p
WHERE p.deleted_at IS NULL
  AND p.code IN (
      'dashboard.read','patients.read','scheduling.read',
      'finance.sales.read','finance.sales.create','finance.sales.update','finance.sales.cancel',
      'finance.payments.create','finance.payments.refund',
      'finance.entries.read','finance.entries.create','finance.entries.delete',
      'finance.cost_centers.manage','finance.reports.read',
      'finance.ap.read','finance.ap.manage',
      'settings.read','files.read','audit.read'
  )
  AND NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='finance' AND d.permission_code=p.code);
