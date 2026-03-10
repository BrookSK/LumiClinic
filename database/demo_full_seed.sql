SET NAMES utf8mb4;
SET time_zone = '+00:00';

START TRANSACTION;

INSERT INTO clinics (
    tenant_key,
    name,
    status,
    contact_email,
    contact_phone,
    contact_whatsapp,
    contact_address,
    contact_website,
    contact_instagram,
    contact_facebook,
    created_at
) VALUES (
    'demo',
    'Clínica Lumi Demo',
    'active',
    'contato@clinicademo.local',
    '+55 11 4000-0000',
    '+55 11 99999-0000',
    'Av. Paulista, 1000 - São Paulo/SP',
    'https://clinicademo.local',
    'https://instagram.com/clinicademo',
    'https://facebook.com/clinicademo',
    NOW()
)
ON DUPLICATE KEY UPDATE
    id = LAST_INSERT_ID(id),
    name = VALUES(name),
    status = VALUES(status),
    contact_email = VALUES(contact_email),
    contact_phone = VALUES(contact_phone),
    contact_whatsapp = VALUES(contact_whatsapp),
    contact_address = VALUES(contact_address),
    contact_website = VALUES(contact_website),
    contact_instagram = VALUES(contact_instagram),
    contact_facebook = VALUES(contact_facebook),
    updated_at = NOW();

SET @clinic_id = LAST_INSERT_ID();

INSERT INTO clinic_domains (clinic_id, domain, is_primary, created_at)
SELECT @clinic_id, 'demo.local', 1, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM clinic_domains d WHERE d.domain = 'demo.local' LIMIT 1
);

INSERT INTO clinic_settings (clinic_id, timezone, language, encryption_key, created_at)
SELECT @clinic_id, 'America/Sao_Paulo', 'pt-BR', REPEAT('a', 64), NOW()
WHERE NOT EXISTS (SELECT 1 FROM clinic_settings cs WHERE cs.clinic_id = @clinic_id LIMIT 1);

UPDATE clinic_settings
   SET evolution_instance = COALESCE(evolution_instance, 'evolution_demo_instance'),
       evolution_apikey_encrypted = COALESCE(evolution_apikey_encrypted, 'demo_apikey_encrypted'),
       openai_api_key_encrypted = COALESCE(openai_api_key_encrypted, 'demo_openai_key_encrypted')
 WHERE clinic_id = @clinic_id;

INSERT INTO whatsapp_templates (clinic_id, code, name, body, status, created_at)
SELECT @clinic_id,
       'reminder_24h',
       'Lembrete 24h',
       'Olá {patient_name}!\n\nLembrete: você tem um agendamento em {date} às {time}.\n\nClínica: {clinic_name}',
       'active',
       NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM whatsapp_templates t
    WHERE t.clinic_id=@clinic_id AND t.code='reminder_24h' AND t.deleted_at IS NULL
    LIMIT 1
);

INSERT INTO whatsapp_templates (clinic_id, code, name, body, status, created_at)
SELECT @clinic_id,
       'reminder_2h',
       'Lembrete 2h',
       'Olá {patient_name}!\n\nSeu agendamento é hoje em {date} às {time}.\n\nClínica: {clinic_name}',
       'active',
       NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM whatsapp_templates t
    WHERE t.clinic_id=@clinic_id AND t.code='reminder_2h' AND t.deleted_at IS NULL
    LIMIT 1
);

INSERT INTO whatsapp_templates (clinic_id, code, name, body, status, created_at)
SELECT @clinic_id,
       'confirm_request',
       'Confirmação de consulta',
       'Olá {patient_name}!\n\nVocê confirma sua consulta em {date} às {time}?\n\nAbra aqui para Confirmar ou Cancelar:\n{confirm_url}\n\nClínica: {clinic_name}',
       'active',
       NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM whatsapp_templates t
    WHERE t.clinic_id=@clinic_id AND t.code='confirm_request' AND t.deleted_at IS NULL
    LIMIT 1
);

INSERT INTO queue_jobs (clinic_id, queue, job_type, payload_json, status, attempts, max_attempts, run_at, created_at)
SELECT @clinic_id,
       'notifications',
       'whatsapp.reminders.reconcile',
       JSON_OBJECT('seed', 1),
       'pending',
       0,
       10,
       (NOW() + INTERVAL 1 MINUTE),
       NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM queue_jobs q
    WHERE q.clinic_id=@clinic_id
      AND q.job_type='whatsapp.reminders.reconcile'
      AND q.status IN ('pending','processing')
    LIMIT 1
);

INSERT INTO clinic_terminology (clinic_id, patient_label, appointment_label, professional_label, created_at)
SELECT @clinic_id, 'Paciente', 'Consulta', 'Profissional', NOW()
WHERE NOT EXISTS (SELECT 1 FROM clinic_terminology ct WHERE ct.clinic_id = @clinic_id LIMIT 1);

INSERT INTO clinic_working_hours (clinic_id, weekday, start_time, end_time, created_at)
SELECT @clinic_id, 1, '08:00:00', '18:00:00', NOW()
WHERE NOT EXISTS (SELECT 1 FROM clinic_working_hours w WHERE w.clinic_id=@clinic_id AND w.weekday=1 AND w.deleted_at IS NULL LIMIT 1);

INSERT INTO clinic_working_hours (clinic_id, weekday, start_time, end_time, created_at)
SELECT @clinic_id, 2, '08:00:00', '18:00:00', NOW()
WHERE NOT EXISTS (SELECT 1 FROM clinic_working_hours w WHERE w.clinic_id=@clinic_id AND w.weekday=2 AND w.deleted_at IS NULL LIMIT 1);

INSERT INTO clinic_working_hours (clinic_id, weekday, start_time, end_time, created_at)
SELECT @clinic_id, 3, '08:00:00', '18:00:00', NOW()
WHERE NOT EXISTS (SELECT 1 FROM clinic_working_hours w WHERE w.clinic_id=@clinic_id AND w.weekday=3 AND w.deleted_at IS NULL LIMIT 1);

INSERT INTO clinic_working_hours (clinic_id, weekday, start_time, end_time, created_at)
SELECT @clinic_id, 4, '08:00:00', '18:00:00', NOW()
WHERE NOT EXISTS (SELECT 1 FROM clinic_working_hours w WHERE w.clinic_id=@clinic_id AND w.weekday=4 AND w.deleted_at IS NULL LIMIT 1);

INSERT INTO clinic_working_hours (clinic_id, weekday, start_time, end_time, created_at)
SELECT @clinic_id, 5, '08:00:00', '18:00:00', NOW()
WHERE NOT EXISTS (SELECT 1 FROM clinic_working_hours w WHERE w.clinic_id=@clinic_id AND w.weekday=5 AND w.deleted_at IS NULL LIMIT 1);

INSERT INTO clinic_closed_days (clinic_id, closed_date, reason, created_at)
SELECT @clinic_id, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Feriado (demo)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM clinic_closed_days d WHERE d.clinic_id=@clinic_id AND d.closed_date=DATE_ADD(CURDATE(), INTERVAL 10 DAY) AND d.deleted_at IS NULL LIMIT 1);

UPDATE clinic_closed_days
   SET is_open = 0
 WHERE clinic_id = @clinic_id
   AND closed_date = DATE_ADD(CURDATE(), INTERVAL 10 DAY)
   AND deleted_at IS NULL;

INSERT INTO clinic_funnel_stages (clinic_id, name, sort_order, status, created_at)
SELECT @clinic_id, 'Novo lead', 1, 'ativo', NOW()
WHERE NOT EXISTS (SELECT 1 FROM clinic_funnel_stages s WHERE s.clinic_id=@clinic_id AND s.name='Novo lead' AND s.deleted_at IS NULL LIMIT 1);

INSERT INTO clinic_funnel_stages (clinic_id, name, sort_order, status, created_at)
SELECT @clinic_id, 'Agendado', 2, 'ativo', NOW()
WHERE NOT EXISTS (SELECT 1 FROM clinic_funnel_stages s WHERE s.clinic_id=@clinic_id AND s.name='Agendado' AND s.deleted_at IS NULL LIMIT 1);

INSERT INTO clinic_lost_reasons (clinic_id, name, sort_order, status, created_at)
SELECT @clinic_id, 'Preço', 1, 'ativo', NOW()
WHERE NOT EXISTS (SELECT 1 FROM clinic_lost_reasons r WHERE r.clinic_id=@clinic_id AND r.name='Preço' AND r.deleted_at IS NULL LIMIT 1);

INSERT INTO clinic_patient_origins (clinic_id, name, sort_order, status, created_at)
SELECT @clinic_id, 'Instagram', 1, 'ativo', NOW()
WHERE NOT EXISTS (SELECT 1 FROM clinic_patient_origins o WHERE o.clinic_id=@clinic_id AND o.name='Instagram' AND o.deleted_at IS NULL LIMIT 1);

SET @funnel_agendado = (SELECT id FROM clinic_funnel_stages s WHERE s.clinic_id=@clinic_id AND s.name='Agendado' AND s.deleted_at IS NULL LIMIT 1);
SET @lost_preco = (SELECT id FROM clinic_lost_reasons r WHERE r.clinic_id=@clinic_id AND r.name='Preço' AND r.deleted_at IS NULL LIMIT 1);
SET @origin_instagram = (SELECT id FROM clinic_patient_origins o WHERE o.clinic_id=@clinic_id AND o.name='Instagram' AND o.deleted_at IS NULL LIMIT 1);

INSERT INTO roles (clinic_id, code, name, is_system, created_at)
VALUES
(@clinic_id, 'owner', 'Owner', 1, NOW()),
(@clinic_id, 'admin', 'Admin', 1, NOW()),
(@clinic_id, 'professional', 'Profissional', 1, NOW()),
(@clinic_id, 'reception', 'Recepção', 1, NOW()),
(@clinic_id, 'finance', 'Financeiro', 1, NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    updated_at = NOW();

SET @role_owner = (SELECT id FROM roles WHERE clinic_id=@clinic_id AND code='owner' AND deleted_at IS NULL LIMIT 1);
SET @role_admin = (SELECT id FROM roles WHERE clinic_id=@clinic_id AND code='admin' AND deleted_at IS NULL LIMIT 1);
SET @role_prof = (SELECT id FROM roles WHERE clinic_id=@clinic_id AND code='professional' AND deleted_at IS NULL LIMIT 1);
SET @role_reception = (SELECT id FROM roles WHERE clinic_id=@clinic_id AND code='reception' AND deleted_at IS NULL LIMIT 1);
SET @role_finance = (SELECT id FROM roles WHERE clinic_id=@clinic_id AND code='finance' AND deleted_at IS NULL LIMIT 1);

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', p.code, 'allow', NOW()
FROM permissions p
WHERE p.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM rbac_role_permission_defaults d
      WHERE d.role_code='owner' AND d.permission_code=p.code
  );

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', p.code, 'allow', NOW()
FROM permissions p
WHERE p.deleted_at IS NULL
  AND p.code <> 'rbac.manage'
  AND NOT EXISTS (
      SELECT 1 FROM rbac_role_permission_defaults d
      WHERE d.role_code='admin' AND d.permission_code=p.code
  );

SET @bcrypt_demo = '$2y$10$K8R7uO5K8xW7XHk7vM9p9e0cK8tZ5m8m7cKQd3Rr8mJQx4S1S2sUu';

INSERT INTO users (clinic_id, name, email, password_hash, is_super_admin, status, created_at)
VALUES (@clinic_id, 'Owner Demo', 'owner@demo.local', @bcrypt_demo, 0, 'active', NOW())
ON DUPLICATE KEY UPDATE
    id = LAST_INSERT_ID(id),
    name = VALUES(name),
    status = VALUES(status),
    updated_at = NOW();
SET @user_owner = LAST_INSERT_ID();

INSERT INTO users (clinic_id, name, email, password_hash, is_super_admin, status, created_at)
VALUES (@clinic_id, 'Admin Demo', 'admin@demo.local', @bcrypt_demo, 0, 'active', NOW())
ON DUPLICATE KEY UPDATE
    id = LAST_INSERT_ID(id),
    name = VALUES(name),
    status = VALUES(status),
    updated_at = NOW();
SET @user_admin = LAST_INSERT_ID();

INSERT INTO users (clinic_id, name, email, password_hash, is_super_admin, status, created_at)
VALUES (@clinic_id, 'Recepção Demo', 'reception@demo.local', @bcrypt_demo, 0, 'active', NOW())
ON DUPLICATE KEY UPDATE
    id = LAST_INSERT_ID(id),
    name = VALUES(name),
    status = VALUES(status),
    updated_at = NOW();
SET @user_reception = LAST_INSERT_ID();

INSERT INTO users (clinic_id, name, email, password_hash, is_super_admin, status, created_at)
VALUES (@clinic_id, 'Financeiro Demo', 'finance@demo.local', @bcrypt_demo, 0, 'active', NOW())
ON DUPLICATE KEY UPDATE
    id = LAST_INSERT_ID(id),
    name = VALUES(name),
    status = VALUES(status),
    updated_at = NOW();
SET @user_finance = LAST_INSERT_ID();

INSERT INTO users (clinic_id, name, email, password_hash, is_super_admin, status, created_at)
VALUES (@clinic_id, 'Dra. Ana Silva', 'pro1@demo.local', @bcrypt_demo, 0, 'active', NOW())
ON DUPLICATE KEY UPDATE
    id = LAST_INSERT_ID(id),
    name = VALUES(name),
    status = VALUES(status),
    updated_at = NOW();
SET @user_prof1 = LAST_INSERT_ID();

INSERT INTO user_roles (clinic_id, user_id, role_id, created_at)
SELECT @clinic_id, @user_owner, @role_owner, NOW()
WHERE NOT EXISTS (SELECT 1 FROM user_roles ur WHERE ur.clinic_id=@clinic_id AND ur.user_id=@user_owner AND ur.role_id=@role_owner AND ur.deleted_at IS NULL);

INSERT INTO user_roles (clinic_id, user_id, role_id, created_at)
SELECT @clinic_id, @user_admin, @role_admin, NOW()
WHERE NOT EXISTS (SELECT 1 FROM user_roles ur WHERE ur.clinic_id=@clinic_id AND ur.user_id=@user_admin AND ur.role_id=@role_admin AND ur.deleted_at IS NULL);

INSERT INTO user_roles (clinic_id, user_id, role_id, created_at)
SELECT @clinic_id, @user_reception, @role_reception, NOW()
WHERE NOT EXISTS (SELECT 1 FROM user_roles ur WHERE ur.clinic_id=@clinic_id AND ur.user_id=@user_reception AND ur.role_id=@role_reception AND ur.deleted_at IS NULL);

INSERT INTO user_roles (clinic_id, user_id, role_id, created_at)
SELECT @clinic_id, @user_finance, @role_finance, NOW()
WHERE NOT EXISTS (SELECT 1 FROM user_roles ur WHERE ur.clinic_id=@clinic_id AND ur.user_id=@user_finance AND ur.role_id=@role_finance AND ur.deleted_at IS NULL);

INSERT INTO user_roles (clinic_id, user_id, role_id, created_at)
SELECT @clinic_id, @user_prof1, @role_prof, NOW()
WHERE NOT EXISTS (SELECT 1 FROM user_roles ur WHERE ur.clinic_id=@clinic_id AND ur.user_id=@user_prof1 AND ur.role_id=@role_prof AND ur.deleted_at IS NULL);

INSERT INTO permission_change_logs (clinic_id, actor_user_id, role_id, action, before_json, after_json, ip_address, created_at)
SELECT @clinic_id, @user_owner, @role_admin, 'grant', JSON_OBJECT('demo', 0), JSON_OBJECT('demo', 1), '127.0.0.1', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM permission_change_logs l
    WHERE l.clinic_id=@clinic_id AND l.actor_user_id=@user_owner AND l.role_id=@role_admin AND l.action='grant'
    LIMIT 1
);

INSERT INTO professionals (clinic_id, name, specialty, allow_online_booking, status, created_at)
SELECT @clinic_id, 'Dra. Ana Silva', 'Dermatologia', 1, 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM professionals p WHERE p.clinic_id=@clinic_id AND p.name='Dra. Ana Silva' AND p.deleted_at IS NULL LIMIT 1
);
SET @prof1 = (SELECT id FROM professionals p WHERE p.clinic_id=@clinic_id AND p.name='Dra. Ana Silva' AND p.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO professional_schedules (clinic_id, professional_id, weekday, start_time, end_time, interval_minutes, created_at)
SELECT @clinic_id, @prof1, 2, '09:00:00', '17:00:00', 15, NOW()
WHERE @prof1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM professional_schedules s
      WHERE s.clinic_id=@clinic_id AND s.professional_id=@prof1 AND s.weekday=2 AND s.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO professionals (clinic_id, name, specialty, allow_online_booking, status, created_at)
SELECT @clinic_id, 'Dr. Bruno Costa', 'Fisioterapia', 1, 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM professionals p WHERE p.clinic_id=@clinic_id AND p.name='Dr. Bruno Costa' AND p.deleted_at IS NULL LIMIT 1
);
SET @prof2 = (SELECT id FROM professionals p WHERE p.clinic_id=@clinic_id AND p.name='Dr. Bruno Costa' AND p.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO professional_schedules (clinic_id, professional_id, weekday, start_time, end_time, interval_minutes, created_at)
SELECT @clinic_id, @prof2, 4, '10:00:00', '16:00:00', 20, NOW()
WHERE @prof2 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM professional_schedules s
      WHERE s.clinic_id=@clinic_id AND s.professional_id=@prof2 AND s.weekday=4 AND s.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO services (clinic_id, name, duration_minutes, price_cents, allow_specific_professional, status, created_at)
SELECT @clinic_id, 'Consulta Avaliação', 45, 15000, 0, 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM services s WHERE s.clinic_id=@clinic_id AND s.name='Consulta Avaliação' AND s.deleted_at IS NULL LIMIT 1
);
SET @svc1 = (SELECT id FROM services s WHERE s.clinic_id=@clinic_id AND s.name='Consulta Avaliação' AND s.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO procedures (clinic_id, name, contraindications, pre_guidelines, post_guidelines, status, created_at)
SELECT @clinic_id, 'Procedimento Laser', 'Gestação; fotossensibilidade.', 'Evitar sol 7 dias.', 'Usar protetor solar.', 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM procedures pr
    WHERE pr.clinic_id=@clinic_id AND pr.name='Procedimento Laser' AND pr.deleted_at IS NULL
    LIMIT 1
);
SET @procedure_laser = (SELECT id FROM procedures pr WHERE pr.clinic_id=@clinic_id AND pr.name='Procedimento Laser' AND pr.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO procedure_protocols (clinic_id, procedure_id, name, notes, sort_order, status, created_at)
SELECT @clinic_id, @procedure_laser, 'Protocolo padrão', 'Protocolo demo', 1, 'active', NOW()
WHERE @procedure_laser IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM procedure_protocols pp
      WHERE pp.clinic_id=@clinic_id AND pp.procedure_id=@procedure_laser AND pp.name='Protocolo padrão' AND pp.deleted_at IS NULL
      LIMIT 1
  );
SET @protocol_laser = (SELECT id FROM procedure_protocols pp WHERE pp.clinic_id=@clinic_id AND pp.procedure_id=@procedure_laser AND pp.name='Protocolo padrão' AND pp.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO procedure_protocol_steps (clinic_id, protocol_id, title, duration_minutes, notes, sort_order, created_at)
SELECT @clinic_id, @protocol_laser, 'Higienização', 10, 'Passo 1', 1, NOW()
WHERE @protocol_laser IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM procedure_protocol_steps s
      WHERE s.clinic_id=@clinic_id AND s.protocol_id=@protocol_laser AND s.title='Higienização' AND s.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO services (clinic_id, name, duration_minutes, price_cents, allow_specific_professional, status, created_at)
SELECT @clinic_id, 'Sessão Laser', 60, 35000, 1, 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM services s WHERE s.clinic_id=@clinic_id AND s.name='Sessão Laser' AND s.deleted_at IS NULL LIMIT 1
);
SET @svc2 = (SELECT id FROM services s WHERE s.clinic_id=@clinic_id AND s.name='Sessão Laser' AND s.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

UPDATE services
   SET procedure_id = @procedure_laser
 WHERE id = @svc2
   AND clinic_id = @clinic_id
   AND @procedure_laser IS NOT NULL;

INSERT INTO service_categories (clinic_id, name, status, created_at)
SELECT @clinic_id, 'Estética', 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM service_categories c
    WHERE c.clinic_id=@clinic_id AND c.name='Estética' AND c.deleted_at IS NULL
    LIMIT 1
);
SET @svc_cat = (SELECT id FROM service_categories c WHERE c.clinic_id=@clinic_id AND c.name='Estética' AND c.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

UPDATE services
   SET category_id = @svc_cat
 WHERE clinic_id=@clinic_id
   AND id IN (@svc1, @svc2)
   AND @svc_cat IS NOT NULL;

INSERT INTO patients (
    clinic_id, name, birth_date, sex, cpf,
    email, phone, whatsapp_opt_in, whatsapp_opt_in_updated_at,
    address, notes, reference_professional_id,
    status, created_at
)
SELECT @clinic_id, 'Mariana Souza', '1993-04-18', 'F', '123.456.789-10',
       'mariana@paciente.local', '+55 11 98888-1111', 1, NOW(),
       'Rua das Flores, 120 - São Paulo/SP', 'Paciente demo para testar portal e prontuário.', @prof1,
       'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM patients p WHERE p.clinic_id=@clinic_id AND p.email='mariana@paciente.local' AND p.deleted_at IS NULL LIMIT 1
);
SET @pat1 = (SELECT id FROM patients p WHERE p.clinic_id=@clinic_id AND p.email='mariana@paciente.local' AND p.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO patients (
    clinic_id, name, birth_date, sex, cpf,
    email, phone, whatsapp_opt_in, whatsapp_opt_in_updated_at,
    address, notes, reference_professional_id,
    status, created_at
)
SELECT @clinic_id, 'João Pereira', '1988-11-02', 'M', '987.654.321-00',
       'joao@paciente.local', '+55 11 97777-2222', 1, NOW(),
       'Av. Central, 55 - São Paulo/SP', 'Paciente demo para testar financeiro e sessões.', @prof2,
       'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM patients p WHERE p.clinic_id=@clinic_id AND p.email='joao@paciente.local' AND p.deleted_at IS NULL LIMIT 1
);
SET @pat2 = (SELECT id FROM patients p WHERE p.clinic_id=@clinic_id AND p.email='joao@paciente.local' AND p.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

UPDATE patients
   SET patient_origin_id = @origin_instagram
 WHERE id IN (@pat1, @pat2)
   AND clinic_id = @clinic_id
   AND @origin_instagram IS NOT NULL;

INSERT INTO patient_contents (clinic_id, type, title, description, url, storage_path, mime_type, procedure_type, audience, status, created_by_user_id, created_at)
SELECT @clinic_id, 'link', 'Cuidados pós laser', 'Conteúdo demo', 'https://clinicademo.local/conteudo/pos-laser', NULL, NULL, 'Sessão Laser', 'all', 'active', @user_admin, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM patient_contents c
    WHERE c.clinic_id=@clinic_id AND c.title='Cuidados pós laser' AND c.deleted_at IS NULL
    LIMIT 1
);
SET @content_post = (SELECT id FROM patient_contents c WHERE c.clinic_id=@clinic_id AND c.title='Cuidados pós laser' AND c.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO patient_content_access (clinic_id, patient_id, content_id, granted_by_user_id, granted_at, created_at)
SELECT @clinic_id, @pat2, @content_post, @user_admin, NOW(), NOW()
WHERE @content_post IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM patient_content_access a
      WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.content_id=@content_post
      LIMIT 1
  );

INSERT INTO patient_events (clinic_id, patient_id, event_code, reference_type, reference_id, meta_json, created_at)
SELECT @clinic_id, @pat1, 'portal_login', 'patient_user', @puser1, JSON_OBJECT('demo', 1), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM patient_events e
    WHERE e.clinic_id=@clinic_id AND e.patient_id=@pat1 AND e.event_code='portal_login'
    LIMIT 1
);

INSERT INTO patient_lgpd_requests (clinic_id, patient_id, type, status, note, processed_by_user_id, processed_at, processed_note, created_at)
SELECT @clinic_id, @pat1, 'export', 'processed', 'Solicitação demo', @user_admin, NOW(), 'Export gerado (demo)', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM patient_lgpd_requests r
    WHERE r.clinic_id=@clinic_id AND r.patient_id=@pat1 AND r.type='export'
      AND r.deleted_at IS NULL
    LIMIT 1
);

INSERT INTO patient_allergies (clinic_id, patient_id, type, trigger_name, reaction, severity, notes, created_by_user_id, created_at)
SELECT @clinic_id, @pat1, 'medication', 'Dipirona', 'Urticária', 'moderate', 'Histórico auto-relatado.', @user_prof1, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM patient_allergies a
    WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat1 AND a.trigger_name='Dipirona' AND a.deleted_at IS NULL
    LIMIT 1
);

INSERT INTO patient_conditions (clinic_id, patient_id, condition_name, status, onset_date, notes, created_by_user_id, created_at)
SELECT @clinic_id, @pat1, 'Dermatite atópica', 'active', '2010-01-01', 'Controle com hidratação e acompanhamento.', @user_prof1, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM patient_conditions c
    WHERE c.clinic_id=@clinic_id AND c.patient_id=@pat1 AND c.condition_name='Dermatite atópica' AND c.deleted_at IS NULL
    LIMIT 1
);

INSERT INTO patient_clinical_alerts (clinic_id, patient_id, title, details, severity, active, created_by_user_id, created_at)
SELECT @clinic_id, @pat2, 'Atenção: hipertensão', 'Paciente relata hipertensão. Confirmar medicações em uso.', 'warning', 1, @user_prof1, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM patient_clinical_alerts a
    WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.title='Atenção: hipertensão' AND a.deleted_at IS NULL
    LIMIT 1
);

INSERT INTO patient_users (clinic_id, patient_id, email, password_hash, status, created_at)
SELECT @clinic_id, @pat1, 'mariana@paciente.local', @bcrypt_demo, 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM patient_users pu WHERE pu.clinic_id=@clinic_id AND pu.email='mariana@paciente.local' AND pu.deleted_at IS NULL LIMIT 1
);
SET @puser1 = (SELECT id FROM patient_users pu WHERE pu.clinic_id=@clinic_id AND pu.email='mariana@paciente.local' AND pu.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO patient_users (clinic_id, patient_id, email, password_hash, status, created_at)
SELECT @clinic_id, @pat2, 'joao@paciente.local', @bcrypt_demo, 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM patient_users pu WHERE pu.clinic_id=@clinic_id AND pu.email='joao@paciente.local' AND pu.deleted_at IS NULL LIMIT 1
);
SET @puser2 = (SELECT id FROM patient_users pu WHERE pu.clinic_id=@clinic_id AND pu.email='joao@paciente.local' AND pu.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO patient_password_resets (clinic_id, patient_user_id, token_hash, expires_at, used_at, created_ip, created_at)
SELECT @clinic_id, @puser1, SHA2(CONCAT('pwreset-', @clinic_id, '-', @puser1), 256), DATE_ADD(NOW(), INTERVAL 1 DAY), NULL, '127.0.0.1', NOW()
WHERE @puser1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM patient_password_resets r
      WHERE r.clinic_id=@clinic_id AND r.patient_user_id=@puser1
      LIMIT 1
  );

INSERT INTO patient_api_tokens (clinic_id, patient_user_id, patient_id, token_hash, name, scopes_json, expires_at, last_used_at, created_at, revoked_at)
SELECT @clinic_id, @puser1, @pat1, SHA2(CONCAT('api-', @clinic_id, '-', @puser1), 256), 'Portal Demo', JSON_ARRAY('portal.read'), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW(), NULL
WHERE @puser1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM patient_api_tokens t
      WHERE t.clinic_id=@clinic_id AND t.patient_user_id=@puser1 AND t.revoked_at IS NULL
      LIMIT 1
  );

INSERT INTO patient_webpush_subscriptions (clinic_id, patient_id, patient_user_id, endpoint, p256dh, auth, user_agent, ip, created_at, updated_at)
SELECT @clinic_id, @pat1, @puser1, 'https://push.demo.local/endpoint/1', 'p256dh_demo', 'auth_demo', 'seed', '127.0.0.1', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM patient_webpush_subscriptions s
    WHERE s.clinic_id=@clinic_id AND s.patient_user_id=@puser1 AND s.deleted_at IS NULL
    LIMIT 1
);

INSERT INTO patient_notifications (clinic_id, patient_id, channel, type, title, body, reference_type, reference_id, created_at)
SELECT @clinic_id, @pat1, 'in_app', 'appointment_confirmed', 'Consulta confirmada', 'Sua consulta foi confirmada. Se precisar reagendar, entre em contato.', 'appointment', NULL, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM patient_notifications n
    WHERE n.clinic_id=@clinic_id AND n.patient_id=@pat1 AND n.type='appointment_confirmed'
    LIMIT 1
);

INSERT INTO user_notifications (clinic_id, user_id, channel, type, title, body, reference_type, reference_id, read_at, created_at)
SELECT @clinic_id, @user_admin, 'in_app', 'system_notice', 'Bem-vindo', 'Notificação demo para admin.', 'clinic', @clinic_id, NULL, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM user_notifications n
    WHERE n.clinic_id=@clinic_id AND n.user_id=@user_admin AND n.type='system_notice' AND n.deleted_at IS NULL
    LIMIT 1
);

INSERT INTO appointments (clinic_id, professional_id, service_id, patient_id, start_at, end_at, status, origin, notes, created_by_user_id, created_at)
SELECT @clinic_id, @prof1, @svc1, @pat1,
       DATE_ADD(NOW(), INTERVAL 2 DAY),
       DATE_ADD(DATE_ADD(NOW(), INTERVAL 2 DAY), INTERVAL 45 MINUTE),
       'scheduled', 'portal', 'Agendado via portal', @user_reception, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM appointments a
    WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat1 AND a.start_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
    LIMIT 1
);

INSERT INTO appointments (clinic_id, professional_id, service_id, patient_id, start_at, end_at, status, origin, notes, created_by_user_id, created_at)
SELECT @clinic_id, @prof2, @svc2, @pat2,
       DATE_SUB(NOW(), INTERVAL 5 DAY),
       DATE_ADD(DATE_SUB(NOW(), INTERVAL 5 DAY), INTERVAL 60 MINUTE),
       'done', 'reception', 'Sessão realizada', @user_reception, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM appointments a
    WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.start_at BETWEEN DATE_SUB(NOW(), INTERVAL 10 DAY) AND NOW()
    LIMIT 1
);

SET @appt_scheduled = (SELECT id FROM appointments a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat1 AND a.status='scheduled' AND a.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO appointments (clinic_id, professional_id, service_id, patient_id, start_at, end_at, status, origin, notes, created_by_user_id, created_at)
SELECT @clinic_id, @prof1, @svc1, @pat1,
       DATE_ADD(NOW(), INTERVAL 3 DAY),
       DATE_ADD(DATE_ADD(NOW(), INTERVAL 3 DAY), INTERVAL 45 MINUTE),
       'confirmed', 'whatsapp', 'Confirmada via WhatsApp', @user_reception, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM appointments a
    WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat1 AND a.status='confirmed' AND a.start_at >= NOW()
    LIMIT 1
);
SET @appt_confirmed = (SELECT id FROM appointments a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat1 AND a.status='confirmed' AND a.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO appointments (clinic_id, professional_id, service_id, patient_id, start_at, end_at, checked_in_at, started_at, status, origin, notes, created_by_user_id, created_at)
SELECT @clinic_id, @prof1, @svc1, @pat2,
       DATE_ADD(NOW(), INTERVAL 1 HOUR),
       DATE_ADD(DATE_ADD(NOW(), INTERVAL 1 HOUR), INTERVAL 45 MINUTE),
       DATE_SUB(NOW(), INTERVAL 10 MINUTE),
       DATE_SUB(NOW(), INTERVAL 5 MINUTE),
       'in_progress', 'reception', 'Em atendimento (demo)', @user_reception, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM appointments a
    WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.status='in_progress'
    LIMIT 1
);
SET @appt_in_progress = (SELECT id FROM appointments a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.status='in_progress' AND a.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO appointments (clinic_id, professional_id, service_id, patient_id, start_at, end_at, status, origin, notes, created_by_user_id, created_at)
SELECT @clinic_id, @prof2, @svc2, @pat1,
       DATE_SUB(NOW(), INTERVAL 20 DAY),
       DATE_ADD(DATE_SUB(NOW(), INTERVAL 20 DAY), INTERVAL 60 MINUTE),
       'cancelled', 'portal', 'Cancelada pelo paciente', @user_reception, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM appointments a
    WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat1 AND a.status='cancelled' AND a.start_at BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND DATE_SUB(NOW(), INTERVAL 10 DAY)
    LIMIT 1
);
SET @appt_cancelled = (SELECT id FROM appointments a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat1 AND a.status='cancelled' AND a.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO appointments (clinic_id, professional_id, service_id, patient_id, start_at, end_at, status, origin, notes, created_by_user_id, created_at)
SELECT @clinic_id, @prof2, @svc2, @pat2,
       DATE_SUB(NOW(), INTERVAL 12 DAY),
       DATE_ADD(DATE_SUB(NOW(), INTERVAL 12 DAY), INTERVAL 60 MINUTE),
       'no_show', 'reception', 'Paciente faltou', @user_reception, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM appointments a
    WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.status='no_show' AND a.start_at BETWEEN DATE_SUB(NOW(), INTERVAL 20 DAY) AND DATE_SUB(NOW(), INTERVAL 5 DAY)
    LIMIT 1
);
SET @appt_no_show = (SELECT id FROM appointments a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.status='no_show' AND a.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

UPDATE appointments
   SET funnel_stage_id = @funnel_agendado
 WHERE id IN (@appt_confirmed, @appt_scheduled)
   AND clinic_id = @clinic_id
   AND @funnel_agendado IS NOT NULL;

UPDATE appointments
   SET lost_reason_id = @lost_preco
 WHERE id = @appt_cancelled
   AND clinic_id = @clinic_id
   AND @lost_preco IS NOT NULL;

INSERT INTO scheduling_blocks (clinic_id, professional_id, start_at, end_at, reason, type, created_by_user_id, created_at)
SELECT @clinic_id, @prof1, DATE_ADD(NOW(), INTERVAL 7 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 7 DAY), INTERVAL 2 HOUR), 'Treinamento (demo)', 'manual', @user_admin, NOW()
WHERE @prof1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM scheduling_blocks b
      WHERE b.clinic_id=@clinic_id AND b.professional_id=@prof1 AND b.reason='Treinamento (demo)' AND b.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO appointment_logs (clinic_id, appointment_id, action, from_json, to_json, user_id, ip_address, created_at)
SELECT @clinic_id, @appt_confirmed, 'status_change', JSON_OBJECT('status','scheduled'), JSON_OBJECT('status','confirmed'), @user_reception, '127.0.0.1', NOW()
WHERE @appt_confirmed IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM appointment_logs l
      WHERE l.clinic_id=@clinic_id AND l.appointment_id=@appt_confirmed AND l.action='status_change'
      LIMIT 1
  );

INSERT INTO whatsapp_message_logs (clinic_id, patient_id, appointment_id, template_code, scheduled_for, status, sent_at, provider_message_id, payload_json, response_json, error_message, created_at)
SELECT @clinic_id, @pat1, @appt_confirmed, 'reminder_24h', DATE_ADD(NOW(), INTERVAL 1 DAY), 'pending', NULL, NULL, JSON_OBJECT('demo', 1), NULL, NULL, NOW()
WHERE @appt_confirmed IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM whatsapp_message_logs w
      WHERE w.clinic_id=@clinic_id AND w.appointment_id=@appt_confirmed AND w.template_code='reminder_24h'
      LIMIT 1
  );

INSERT INTO appointment_confirmation_tokens (clinic_id, appointment_id, kind, token_hash, expires_at, used_at, used_action, created_at)
SELECT @clinic_id, @appt_confirmed, 'confirm', SHA2(CONCAT('demo-confirm-', @clinic_id, '-', @appt_confirmed), 256), DATE_ADD(NOW(), INTERVAL 2 DAY), NULL, NULL, NOW()
WHERE @appt_confirmed IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM appointment_confirmation_tokens t
      WHERE t.clinic_id=@clinic_id AND t.appointment_id=@appt_confirmed AND t.kind='confirm'
      LIMIT 1
  );

INSERT INTO patient_appointment_requests (clinic_id, patient_id, appointment_id, type, status, requested_start_at, note, created_at)
SELECT @clinic_id, @pat1, @appt_confirmed, 'reschedule', 'pending', DATE_ADD(NOW(), INTERVAL 4 DAY), 'Posso trocar para outro horário?', NOW()
WHERE @appt_confirmed IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM patient_appointment_requests r
      WHERE r.clinic_id=@clinic_id AND r.patient_id=@pat1 AND r.appointment_id=@appt_confirmed AND r.type='reschedule'
      LIMIT 1
  );

INSERT INTO sales (clinic_id, patient_id, total_bruto, desconto, total_liquido, status, origin, notes, created_by_user_id, created_at)
SELECT @clinic_id, @pat2, 350.00, 0.00, 350.00, 'paid', 'reception', 'Venda referente à Sessão Laser', @user_finance, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM sales s WHERE s.clinic_id=@clinic_id AND s.patient_id=@pat2 AND s.total_liquido=350.00 AND s.deleted_at IS NULL LIMIT 1
);
SET @sale1 = (SELECT id FROM sales s WHERE s.clinic_id=@clinic_id AND s.patient_id=@pat2 AND s.total_liquido=350.00 AND s.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO cost_centers (clinic_id, name, status, created_at)
SELECT @clinic_id, 'Marketing', 'active', NOW()
WHERE NOT EXISTS (SELECT 1 FROM cost_centers cc WHERE cc.clinic_id=@clinic_id AND cc.name='Marketing' AND cc.deleted_at IS NULL LIMIT 1);
SET @cc_marketing = (SELECT id FROM cost_centers cc WHERE cc.clinic_id=@clinic_id AND cc.name='Marketing' AND cc.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO cost_centers (clinic_id, name, status, created_at)
SELECT @clinic_id, 'Operação', 'active', NOW()
WHERE NOT EXISTS (SELECT 1 FROM cost_centers cc WHERE cc.clinic_id=@clinic_id AND cc.name='Operação' AND cc.deleted_at IS NULL LIMIT 1);
SET @cc_ops = (SELECT id FROM cost_centers cc WHERE cc.clinic_id=@clinic_id AND cc.name='Operação' AND cc.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO financial_entries (clinic_id, kind, occurred_on, amount, method, status, cost_center_id, sale_id, payment_id, description, created_by_user_id, created_at)
SELECT @clinic_id, 'income', CURDATE(), 350.00, 'credit_card', 'posted', NULL, @sale1,
       (SELECT id FROM payments p WHERE p.sale_id=@sale1 AND p.status='paid' AND p.deleted_at IS NULL ORDER BY id DESC LIMIT 1),
       'Recebimento venda Sessão Laser', @user_finance, NOW()
WHERE @sale1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM financial_entries fe
      WHERE fe.clinic_id=@clinic_id AND fe.sale_id=@sale1 AND fe.kind='income' AND fe.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO accounts_payable (clinic_id, vendor_name, title, description, cost_center_id, payable_type, status, start_due_date, total_installments, recurrence_interval, recurrence_until, created_by_user_id, created_at)
SELECT @clinic_id, 'Fornecedor XYZ', 'Aluguel', 'Conta demo para telas de contas a pagar', @cc_ops, 'recurring', 'active', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 12, 'month', DATE_ADD(CURDATE(), INTERVAL 365 DAY), @user_finance, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM accounts_payable ap
    WHERE ap.clinic_id=@clinic_id AND ap.title='Aluguel' AND ap.deleted_at IS NULL
    LIMIT 1
);
SET @ap_rent = (SELECT id FROM accounts_payable ap WHERE ap.clinic_id=@clinic_id AND ap.title='Aluguel' AND ap.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO accounts_payable_installments (clinic_id, payable_id, installment_no, due_date, amount, status, paid_at, created_at)
SELECT @clinic_id, @ap_rent, 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 2500.00, 'open', NULL, NOW()
WHERE @ap_rent IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM accounts_payable_installments i
      WHERE i.clinic_id=@clinic_id AND i.payable_id=@ap_rent AND i.installment_no=1 AND i.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO accounts_payable_installments (clinic_id, payable_id, installment_no, due_date, amount, status, paid_at, created_at)
SELECT @clinic_id, @ap_rent, 2, DATE_ADD(CURDATE(), INTERVAL 35 DAY), 2500.00, 'open', NULL, NOW()
WHERE @ap_rent IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM accounts_payable_installments i
      WHERE i.clinic_id=@clinic_id AND i.payable_id=@ap_rent AND i.installment_no=2 AND i.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO sale_items (clinic_id, sale_id, type, reference_id, quantity, unit_price, subtotal, created_at)
SELECT @clinic_id, @sale1, 'service', @svc2, 1, 350.00, 350.00, NOW()
WHERE @sale1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM sale_items si WHERE si.sale_id=@sale1 AND si.type='service' AND si.reference_id=@svc2 AND si.deleted_at IS NULL LIMIT 1
  );

INSERT INTO packages (clinic_id, name, service_id, total_sessions, validity_days, price, status, created_at)
SELECT @clinic_id, 'Pacote Laser 5 sessões', @svc2, 5, 180, 1500.00, 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM packages p
    WHERE p.clinic_id=@clinic_id AND p.name='Pacote Laser 5 sessões' AND p.deleted_at IS NULL
    LIMIT 1
);
SET @pkg_laser = (SELECT id FROM packages p WHERE p.clinic_id=@clinic_id AND p.name='Pacote Laser 5 sessões' AND p.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO patient_packages (clinic_id, patient_id, package_id, sale_id, sale_item_id, total_sessions, used_sessions, valid_until, status, created_at)
SELECT @clinic_id, @pat2, @pkg_laser, @sale1,
       (SELECT id FROM sale_items si WHERE si.sale_id=@sale1 AND si.type='service' AND si.reference_id=@svc2 AND si.deleted_at IS NULL ORDER BY id DESC LIMIT 1),
       5, 1, DATE_ADD(CURDATE(), INTERVAL 180 DAY), 'active', NOW()
WHERE @pkg_laser IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM patient_packages pp
      WHERE pp.clinic_id=@clinic_id AND pp.patient_id=@pat2 AND pp.package_id=@pkg_laser AND pp.deleted_at IS NULL
      LIMIT 1
  );

SET @ppkg1 = (SELECT id FROM patient_packages pp WHERE pp.clinic_id=@clinic_id AND pp.patient_id=@pat2 AND pp.package_id=@pkg_laser AND pp.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

UPDATE appointments
   SET patient_package_id = @ppkg1
 WHERE clinic_id = @clinic_id
   AND patient_id = @pat2
   AND status IN ('done','in_progress')
   AND deleted_at IS NULL
   AND @ppkg1 IS NOT NULL;

INSERT INTO appointment_package_sessions (clinic_id, appointment_id, patient_package_id, consumed_at, created_by_user_id, created_at)
SELECT @clinic_id,
       (SELECT id FROM appointments a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.status='done' AND a.deleted_at IS NULL ORDER BY id DESC LIMIT 1),
       @ppkg1, NOW(), @user_prof1, NOW()
WHERE @ppkg1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM appointment_package_sessions s
      WHERE s.appointment_id=(SELECT id FROM appointments a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.status='done' AND a.deleted_at IS NULL ORDER BY id DESC LIMIT 1)
      LIMIT 1
  );

INSERT INTO subscription_plans (clinic_id, name, interval_months, price, status, created_at)
SELECT @clinic_id, 'Mensal Premium', 1, 299.00, 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM subscription_plans sp
    WHERE sp.clinic_id=@clinic_id AND sp.name='Mensal Premium' AND sp.deleted_at IS NULL
    LIMIT 1
);
SET @subplan = (SELECT id FROM subscription_plans sp WHERE sp.clinic_id=@clinic_id AND sp.name='Mensal Premium' AND sp.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO patient_subscriptions (clinic_id, patient_id, plan_id, sale_id, sale_item_id, status, started_at, ends_at, created_at)
SELECT @clinic_id, @pat1, @subplan, NULL, NULL, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NOW()
WHERE @subplan IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM patient_subscriptions ps
      WHERE ps.clinic_id=@clinic_id AND ps.patient_id=@pat1 AND ps.plan_id=@subplan AND ps.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO material_categories (clinic_id, name, status, created_at)
SELECT @clinic_id, 'Consumíveis', 'active', NOW()
WHERE NOT EXISTS (SELECT 1 FROM material_categories c WHERE c.clinic_id=@clinic_id AND c.name='Consumíveis' AND c.deleted_at IS NULL LIMIT 1);
SET @mat_cat = (SELECT id FROM material_categories c WHERE c.clinic_id=@clinic_id AND c.name='Consumíveis' AND c.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO material_units (clinic_id, code, name, status, created_at)
SELECT @clinic_id, 'un', 'Unidade', 'active', NOW()
WHERE NOT EXISTS (SELECT 1 FROM material_units u WHERE u.clinic_id=@clinic_id AND u.code='un' AND u.deleted_at IS NULL LIMIT 1);

INSERT INTO marketing_templates (clinic_id, channel, name, subject, body, status, created_by_user_id, created_at)
SELECT @clinic_id, 'email', 'Boas-vindas', 'Bem-vindo(a) à Clínica Lumi Demo', 'Olá {patient_name}! Seja bem-vindo(a).', 'active', @user_admin, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM marketing_templates t
    WHERE t.clinic_id=@clinic_id AND t.channel='email' AND t.name='Boas-vindas' AND t.deleted_at IS NULL
    LIMIT 1
);
SET @mkt_tpl_welcome = (SELECT id FROM marketing_templates t WHERE t.clinic_id=@clinic_id AND t.name='Boas-vindas' AND t.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO marketing_triggers (clinic_id, code, name, config_json, status, created_by_user_id, created_at)
SELECT @clinic_id, 'appointment_completed', 'Consulta concluída', JSON_OBJECT('days_after', 2), 'active', @user_admin, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM marketing_triggers trg
    WHERE trg.clinic_id=@clinic_id AND trg.code='appointment_completed' AND trg.deleted_at IS NULL
    LIMIT 1
);
SET @mkt_trg_completed = (SELECT id FROM marketing_triggers trg WHERE trg.clinic_id=@clinic_id AND trg.code='appointment_completed' AND trg.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO marketing_rules (clinic_id, name, description, trigger_id, template_id, conditions_json, action_json, status, created_by_user_id, created_at)
SELECT @clinic_id, 'Follow-up pós consulta', 'Envia mensagem 2 dias após consulta concluída', @mkt_trg_completed, @mkt_tpl_welcome,
       JSON_OBJECT('service_id', @svc1),
       JSON_OBJECT('channel', 'email', 'delay_days', 2),
       'active', @user_admin, NOW()
WHERE @mkt_trg_completed IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM marketing_rules r
      WHERE r.clinic_id=@clinic_id AND r.name='Follow-up pós consulta' AND r.deleted_at IS NULL
      LIMIT 1
  );
SET @mkt_rule_follow = (SELECT id FROM marketing_rules r WHERE r.clinic_id=@clinic_id AND r.name='Follow-up pós consulta' AND r.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO marketing_events (clinic_id, event_code, entity_type, entity_id, payload_json, created_at)
SELECT @clinic_id, 'appointment.completed', 'appointment', @appt_no_show, JSON_OBJECT('demo', 1), NOW()
WHERE @appt_no_show IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM marketing_events e
      WHERE e.clinic_id=@clinic_id AND e.event_code='appointment.completed' AND e.entity_id=@appt_no_show
      LIMIT 1
  );

INSERT INTO marketing_outbox (clinic_id, rule_id, template_id, channel, recipient, subject, body, status, scheduled_at, created_at)
SELECT @clinic_id, @mkt_rule_follow, @mkt_tpl_welcome, 'email', 'mariana@paciente.local', 'Bem-vindo(a) à Clínica Lumi Demo', 'Olá Mariana! Obrigado por comparecer.', 'pending', DATE_ADD(NOW(), INTERVAL 2 DAY), NOW()
WHERE @mkt_rule_follow IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM marketing_outbox o
      WHERE o.clinic_id=@clinic_id AND o.recipient='mariana@paciente.local' AND o.status='pending'
      LIMIT 1
  );

INSERT INTO marketing_segments (clinic_id, name, status, rules_json, created_by_user_id, created_at)
SELECT @clinic_id, 'Pacientes ativos (demo)', 'active', JSON_OBJECT('status', 'active'), @user_admin, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM marketing_segments s
    WHERE s.clinic_id=@clinic_id AND s.name='Pacientes ativos (demo)' AND s.deleted_at IS NULL
    LIMIT 1
);
SET @mkt_seg_active = (SELECT id FROM marketing_segments s WHERE s.clinic_id=@clinic_id AND s.name='Pacientes ativos (demo)' AND s.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO marketing_campaigns (clinic_id, name, channel, segment_id, whatsapp_template_code, email_subject, email_body, click_url, status, scheduled_for, created_by_user_id, created_at)
SELECT @clinic_id, 'Campanha Reativação', 'email', @mkt_seg_active, NULL,
       'Hora de voltar', 'Olá {patient_name}, que tal agendar uma nova sessão?', 'https://clinicademo.local/agendar',
       'scheduled', DATE_ADD(NOW(), INTERVAL 1 DAY), @user_admin, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM marketing_campaigns c
    WHERE c.clinic_id=@clinic_id AND c.name='Campanha Reativação' AND c.deleted_at IS NULL
    LIMIT 1
);
SET @mkt_campaign = (SELECT id FROM marketing_campaigns c WHERE c.clinic_id=@clinic_id AND c.name='Campanha Reativação' AND c.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

UPDATE marketing_campaigns
   SET trigger_event = COALESCE(trigger_event, 'appointment_completed'),
       trigger_delay_minutes = COALESCE(trigger_delay_minutes, 120)
 WHERE clinic_id = @clinic_id
   AND id = @mkt_campaign;

INSERT INTO marketing_campaign_messages (clinic_id, campaign_id, patient_id, channel, status, scheduled_for, click_token, click_url_snapshot, payload_json, created_at)
SELECT @clinic_id, @mkt_campaign, @pat1, 'email', 'queued', DATE_ADD(NOW(), INTERVAL 1 DAY), 'demo_click_001', 'https://clinicademo.local/agendar', JSON_OBJECT('demo', 1), NOW()
WHERE @mkt_campaign IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM marketing_campaign_messages m
      WHERE m.clinic_id=@clinic_id AND m.campaign_id=@mkt_campaign AND m.patient_id=@pat1 AND m.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO marketing_calendar_entries (clinic_id, entry_date, content_type, status, title, notes, assigned_user_id, created_by_user_id, created_at)
SELECT @clinic_id, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'post', 'planned', 'Post: Cuidados com a pele no inverno', 'Conteúdo demo para calendário.', @user_admin, @user_admin, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM marketing_calendar_entries e
    WHERE e.clinic_id=@clinic_id AND e.title='Post: Cuidados com a pele no inverno' AND e.deleted_at IS NULL
    LIMIT 1
);

INSERT INTO whatsapp_message_logs (clinic_id, patient_id, appointment_id, template_code, scheduled_for, status, sent_at, provider_message_id, payload_json, response_json, error_message, created_at)
SELECT @clinic_id, @pat1, @appt_confirmed, 'confirm_request', DATE_ADD(NOW(), INTERVAL 1 HOUR), 'sent', NOW(), 'wa_demo_001',
       JSON_OBJECT('to', '+5511988881111'), JSON_OBJECT('ok', true), NULL, NOW()
WHERE @appt_confirmed IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM whatsapp_message_logs w
      WHERE w.clinic_id=@clinic_id AND w.appointment_id=@appt_confirmed AND w.template_code='confirm_request'
      LIMIT 1
  );

INSERT INTO anamnesis_templates (clinic_id, name, status, created_at)
SELECT @clinic_id, 'Anamnese Geral', 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM anamnesis_templates t
    WHERE t.clinic_id=@clinic_id AND t.name='Anamnese Geral' AND t.deleted_at IS NULL
    LIMIT 1
);
SET @anam_tpl = (SELECT id FROM anamnesis_templates t WHERE t.clinic_id=@clinic_id AND t.name='Anamnese Geral' AND t.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

UPDATE clinic_settings
   SET anamnesis_default_template_id = @anam_tpl
 WHERE clinic_id = @clinic_id
   AND @anam_tpl IS NOT NULL;

INSERT INTO anamnesis_template_fields (clinic_id, template_id, field_key, label, field_type, options_json, sort_order, created_at)
SELECT @clinic_id, @anam_tpl, 'chief_complaint', 'Queixa principal', 'textarea', NULL, 1, NOW()
WHERE @anam_tpl IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM anamnesis_template_fields f
      WHERE f.template_id=@anam_tpl AND f.field_key='chief_complaint' AND f.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO appointment_anamnesis_requests (clinic_id, appointment_id, patient_id, template_id, token_hash, token_encrypted, expires_at, used_at, used_action, response_id, created_by_user_id, created_at)
SELECT @clinic_id, @appt_confirmed, @pat1, @anam_tpl,
       SHA2(CONCAT('demo-anam-', @clinic_id, '-', @appt_confirmed), 256), NULL,
       DATE_ADD(NOW(), INTERVAL 7 DAY), NULL, NULL,
       (SELECT id FROM anamnesis_responses r WHERE r.clinic_id=@clinic_id AND r.patient_id=@pat1 AND r.template_id=@anam_tpl AND r.deleted_at IS NULL ORDER BY id DESC LIMIT 1),
       @user_reception, NOW()
WHERE @appt_confirmed IS NOT NULL AND @anam_tpl IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM appointment_anamnesis_requests ar
      WHERE ar.clinic_id=@clinic_id AND ar.appointment_id=@appt_confirmed AND ar.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO anamnesis_responses (clinic_id, patient_id, template_id, professional_id, answers_json, created_by_user_id, created_at)
SELECT @clinic_id, @pat1, @anam_tpl, @prof1,
       JSON_OBJECT('chief_complaint', 'Coceira e vermelhidão há 2 semanas.'),
       @user_prof1, NOW()
WHERE @anam_tpl IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM anamnesis_responses r
      WHERE r.clinic_id=@clinic_id AND r.patient_id=@pat1 AND r.template_id=@anam_tpl AND r.deleted_at IS NULL
      LIMIT 1
  );

UPDATE anamnesis_responses
   SET template_name_snapshot = COALESCE(template_name_snapshot, 'Anamnese Geral'),
       template_updated_at_snapshot = COALESCE(template_updated_at_snapshot, NOW()),
       fields_snapshot_json = COALESCE(fields_snapshot_json, JSON_ARRAY(JSON_OBJECT('field_key','chief_complaint','label','Queixa principal','field_type','textarea')))
 WHERE clinic_id = @clinic_id
   AND patient_id = @pat1
   AND template_id = @anam_tpl
   AND deleted_at IS NULL;

INSERT INTO consent_terms (clinic_id, procedure_type, title, body, status, created_at)
SELECT @clinic_id, 'Sessão Laser', 'Consentimento - Sessão Laser', 'Termo demo de consentimento para procedimento.', 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM consent_terms t
    WHERE t.clinic_id=@clinic_id AND t.procedure_type='Sessão Laser' AND t.deleted_at IS NULL
    LIMIT 1
);
SET @consent_term = (SELECT id FROM consent_terms t WHERE t.clinic_id=@clinic_id AND t.procedure_type='Sessão Laser' AND t.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO consent_acceptances (clinic_id, term_id, patient_id, procedure_type, accepted_by_user_id, ip_address, accepted_at, created_at)
SELECT @clinic_id, @consent_term, @pat2, 'Sessão Laser', @user_reception, '127.0.0.1', NOW(), NOW()
WHERE @consent_term IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM consent_acceptances a
      WHERE a.clinic_id=@clinic_id AND a.term_id=@consent_term AND a.patient_id=@pat2
      LIMIT 1
  );

UPDATE consent_acceptances
   SET term_procedure_type_snapshot = COALESCE(term_procedure_type_snapshot, 'Sessão Laser'),
       term_title_snapshot = COALESCE(term_title_snapshot, 'Consentimento - Sessão Laser'),
       term_body_snapshot = COALESCE(term_body_snapshot, 'Termo demo de consentimento para procedimento.'),
       term_updated_at_snapshot = COALESCE(term_updated_at_snapshot, NOW())
 WHERE clinic_id = @clinic_id
   AND term_id = @consent_term
   AND patient_id = @pat2;

INSERT INTO medical_records (clinic_id, patient_id, professional_id, attended_at, procedure_type, clinical_description, clinical_evolution, notes, ai_transcript, ai_summary, ai_report, created_by_user_id, created_at)
SELECT @clinic_id, @pat2, @prof2, DATE_SUB(NOW(), INTERVAL 5 DAY), 'Sessão Laser',
       'Avaliação inicial do procedimento.', 'Evolução favorável. Sem intercorrências.', 'Observações demo.',
       'Transcrição demo', 'Resumo demo', 'Relatório demo',
       @user_prof1, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM medical_records mr
    WHERE mr.clinic_id=@clinic_id AND mr.patient_id=@pat2 AND mr.procedure_type='Sessão Laser' AND mr.deleted_at IS NULL
    LIMIT 1
);
SET @mr1 = (SELECT id FROM medical_records mr WHERE mr.clinic_id=@clinic_id AND mr.patient_id=@pat2 AND mr.procedure_type='Sessão Laser' AND mr.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO medical_record_audio_notes (clinic_id, patient_id, medical_record_id, appointment_id, professional_id, storage_path, original_filename, mime_type, size_bytes, status, transcript_text, transcribed_at, created_by_user_id, created_at)
SELECT @clinic_id, @pat2, @mr1,
       (SELECT id FROM appointments a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.status='done' AND a.deleted_at IS NULL ORDER BY id DESC LIMIT 1),
       @prof2,
       'demo/audio_notes/note_001.webm', 'note.webm', 'audio/webm', 2048,
       'uploaded', 'Transcrição demo do áudio.', NOW(), @user_prof1, NOW()
WHERE @mr1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM medical_record_audio_notes n
      WHERE n.clinic_id=@clinic_id AND n.patient_id=@pat2 AND n.storage_path='demo/audio_notes/note_001.webm' AND n.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO medical_record_versions (clinic_id, medical_record_id, version_no, snapshot_json, edited_by_user_id, ip_address, created_at)
SELECT @clinic_id, @mr1, 1,
       JSON_OBJECT('clinical_description', 'Avaliação inicial do procedimento.', 'clinical_evolution', 'Evolução favorável.'),
       @user_prof1, '127.0.0.1', NOW()
WHERE @mr1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM medical_record_versions v
      WHERE v.medical_record_id=@mr1 AND v.version_no=1
      LIMIT 1
  );

INSERT INTO medical_images (clinic_id, patient_id, medical_record_id, professional_id, kind, taken_at, procedure_type, storage_path, original_filename, mime_type, size_bytes, created_by_user_id, created_at)
SELECT @clinic_id, @pat2, @mr1, @prof2, 'before', DATE_SUB(NOW(), INTERVAL 5 DAY), 'Sessão Laser',
       'demo/medical_images/before_001.jpg', 'before.jpg', 'image/jpeg', 123456, @user_prof1, NOW()
WHERE @mr1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM medical_images mi
      WHERE mi.clinic_id=@clinic_id AND mi.patient_id=@pat2 AND mi.storage_path='demo/medical_images/before_001.jpg' AND mi.deleted_at IS NULL
      LIMIT 1
  );

SET @mi1 = (SELECT id FROM medical_images mi WHERE mi.clinic_id=@clinic_id AND mi.patient_id=@pat2 AND mi.storage_path='demo/medical_images/before_001.jpg' AND mi.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

UPDATE medical_images
   SET session_number = COALESCE(session_number, 1),
       pose = COALESCE(pose, 'front')
 WHERE id = @mi1
   AND clinic_id = @clinic_id;

INSERT INTO medical_image_annotations (clinic_id, medical_image_id, payload_json, note, created_by_user_id, created_at)
SELECT @clinic_id, @mi1, JSON_OBJECT('type', 'circle', 'x', 0.5, 'y', 0.5, 'r', 0.1), 'Marcação demo', @user_prof1, NOW()
WHERE @mi1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM medical_image_annotations a
      WHERE a.clinic_id=@clinic_id AND a.medical_image_id=@mi1 AND a.note='Marcação demo' AND a.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO patient_uploads (clinic_id, patient_id, patient_user_id, kind, taken_at, note, storage_path, original_filename, mime_type, size_bytes, status, moderated_by_user_id, moderated_at, moderation_note, medical_image_id, created_at)
SELECT @clinic_id, @pat2, @puser2, 'after', DATE_SUB(NOW(), INTERVAL 1 DAY), 'Foto pós (demo)', 'demo/patient_uploads/after_001.jpg', 'after.jpg', 'image/jpeg', 12345,
       'approved', @user_admin, NOW(), 'Aprovado (demo)', @mi1, NOW()
WHERE @mi1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM patient_uploads u
      WHERE u.clinic_id=@clinic_id AND u.patient_id=@pat2 AND u.storage_path='demo/patient_uploads/after_001.jpg' AND u.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO medical_record_templates (clinic_id, name, status, created_at)
SELECT @clinic_id, 'Template Dermatologia', 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM medical_record_templates t
    WHERE t.clinic_id=@clinic_id AND t.name='Template Dermatologia' AND t.deleted_at IS NULL
    LIMIT 1
);
SET @mrt = (SELECT id FROM medical_record_templates t WHERE t.clinic_id=@clinic_id AND t.name='Template Dermatologia' AND t.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO medical_record_template_fields (clinic_id, template_id, field_key, label, field_type, required, options_json, sort_order, created_at)
SELECT @clinic_id, @mrt, 'skin_type', 'Fototipo', 'select', 0, JSON_ARRAY('I','II','III','IV','V','VI'), 1, NOW()
WHERE @mrt IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM medical_record_template_fields f
      WHERE f.template_id=@mrt AND f.field_key='skin_type' AND f.deleted_at IS NULL
      LIMIT 1
  );

UPDATE medical_records
   SET template_id = @mrt,
       template_name_snapshot = 'Template Dermatologia',
       template_updated_at_snapshot = NOW(),
       template_fields_snapshot_json = JSON_ARRAY(JSON_OBJECT('field_key','skin_type','label','Fototipo','field_type','select')),
       fields_json = JSON_OBJECT('skin_type','III')
 WHERE id = @mr1
   AND clinic_id = @clinic_id
   AND @mrt IS NOT NULL;

INSERT INTO consultations (clinic_id, appointment_id, patient_id, professional_id, executed_at, notes, created_by_user_id, created_at)
SELECT @clinic_id,
       (SELECT id FROM appointments a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.status='done' AND a.deleted_at IS NULL ORDER BY id DESC LIMIT 1),
       @pat2, @prof2, DATE_SUB(NOW(), INTERVAL 5 DAY), 'Consulta executada (demo).', @user_prof1, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM consultations c
    WHERE c.clinic_id=@clinic_id AND c.patient_id=@pat2 AND c.deleted_at IS NULL
    LIMIT 1
);
SET @consult1 = (SELECT id FROM consultations c WHERE c.clinic_id=@clinic_id AND c.patient_id=@pat2 AND c.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO consultation_attachments (clinic_id, consultation_id, patient_id, storage_path, original_filename, mime_type, size_bytes, created_by_user_id, created_at)
SELECT @clinic_id, @consult1, @pat2, 'demo/consultations/attachment_001.pdf', 'exame.pdf', 'application/pdf', 45678, @user_prof1, NOW()
WHERE @consult1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM consultation_attachments a
      WHERE a.clinic_id=@clinic_id AND a.consultation_id=@consult1 AND a.storage_path='demo/consultations/attachment_001.pdf' AND a.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO consent_terms (clinic_id, procedure_type, title, body, status, created_at)
SELECT @clinic_id, 'Consulta Avaliação', 'Consentimento - Consulta', 'Termo demo de consentimento para consulta.', 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM consent_terms t
    WHERE t.clinic_id=@clinic_id AND t.procedure_type='Consulta Avaliação' AND t.deleted_at IS NULL
    LIMIT 1
);

INSERT INTO signatures (clinic_id, patient_id, term_acceptance_id, medical_record_id, storage_path, mime_type, signed_by_user_id, ip_address, created_at)
SELECT @clinic_id, @pat2,
       (SELECT id FROM consent_acceptances a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 ORDER BY id DESC LIMIT 1),
       @mr1,
       'demo/signatures/sign_001.png', 'image/png', @user_reception, '127.0.0.1', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM signatures s
    WHERE s.clinic_id=@clinic_id AND s.patient_id=@pat2 AND s.storage_path='demo/signatures/sign_001.png'
    LIMIT 1
);

INSERT INTO legal_documents (clinic_id, scope, target_role_code, title, body, is_required, status, created_at)
SELECT @clinic_id, 'patient_portal', NULL, 'Termos do Portal', 'Termo legal demo do portal.', 1, 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM legal_documents d
    WHERE d.clinic_id=@clinic_id AND d.scope='patient_portal' AND d.title='Termos do Portal' AND d.deleted_at IS NULL
    LIMIT 1
);
SET @ld_portal = (SELECT id FROM legal_documents d WHERE d.clinic_id=@clinic_id AND d.scope='patient_portal' AND d.title='Termos do Portal' AND d.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO legal_document_versions (clinic_id, document_id, version_number, title, body, hash_sha256, created_at)
SELECT @clinic_id, @ld_portal, 1, 'Termos do Portal (v1)', 'Termo legal demo do portal.', SHA2(CONCAT('ld-', @ld_portal, '-v1'), 256), NOW()
WHERE @ld_portal IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM legal_document_versions v
      WHERE v.document_id=@ld_portal AND v.version_number=1
      LIMIT 1
  );
SET @ldv_portal = (SELECT id FROM legal_document_versions v WHERE v.document_id=@ld_portal AND v.version_number=1 LIMIT 1);

INSERT INTO legal_document_acceptances (clinic_id, document_id, document_version_id, patient_user_id, user_id, accepted_at, ip_address, user_agent, created_at)
SELECT @clinic_id, @ld_portal, @ldv_portal, @puser1, NULL, NOW(), '127.0.0.1', 'seed', NOW()
WHERE @ld_portal IS NOT NULL AND @ldv_portal IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM legal_document_acceptances a
      WHERE a.document_id=@ld_portal AND a.patient_user_id=@puser1
      LIMIT 1
  );

INSERT INTO legal_document_signatures (clinic_id, document_id, document_version_id, patient_user_id, user_id, method, signature_data_url, signature_hash_sha256, signed_at, ip_address, user_agent, created_at)
SELECT @clinic_id, @ld_portal, @ldv_portal, @puser1, NULL, 'draw', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB', SHA2(CONCAT('sig-', @ldv_portal, '-', @puser1), 256), NOW(), '127.0.0.1', 'seed', NOW()
WHERE @ld_portal IS NOT NULL AND @ldv_portal IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM legal_document_signatures s
      WHERE s.document_version_id=@ldv_portal AND s.patient_user_id=@puser1
      LIMIT 1
  );

INSERT INTO compliance_policies (clinic_id, code, title, description, status, version, owner_user_id, reviewed_at, next_review_at, created_at)
SELECT @clinic_id, 'lgpd', 'Política LGPD', 'Política demo de privacidade.', 'active', 1, @user_owner, NOW(), DATE_ADD(NOW(), INTERVAL 180 DAY), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM compliance_policies p
    WHERE p.clinic_id=@clinic_id AND p.code='lgpd'
    LIMIT 1
);
SET @cp_lgpd = (SELECT id FROM compliance_policies p WHERE p.clinic_id=@clinic_id AND p.code='lgpd' LIMIT 1);

INSERT INTO compliance_controls (clinic_id, policy_id, code, title, description, status, owner_user_id, evidence_url, last_tested_at, created_at)
SELECT @clinic_id, @cp_lgpd, 'backup', 'Backups', 'Evidência demo de backups.', 'implemented', @user_admin, 'https://clinicademo.local/evidencias/backup', NOW(), NOW()
WHERE @cp_lgpd IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM compliance_controls c
      WHERE c.clinic_id=@clinic_id AND c.code='backup'
      LIMIT 1
  );

INSERT INTO patient_profile_change_requests (clinic_id, patient_id, patient_user_id, status, requested_fields_json, created_at)
SELECT @clinic_id, @pat1, @puser1, 'pending', JSON_OBJECT('phone', '+55 11 90000-0000', 'address', 'Rua Nova, 999 - SP'), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM patient_profile_change_requests r
    WHERE r.clinic_id=@clinic_id AND r.patient_id=@pat1 AND r.status='pending'
    LIMIT 1
);

INSERT INTO payments (clinic_id, sale_id, method, amount, status, fees, gateway_ref, paid_at, created_by_user_id, created_at)
SELECT @clinic_id, @sale1, 'credit_card', 350.00, 'paid', 0.00, 'demo_txn_001', NOW(), @user_finance, NOW()
WHERE @sale1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM payments p WHERE p.sale_id=@sale1 AND p.status='paid' AND p.deleted_at IS NULL LIMIT 1
  );

INSERT INTO sale_logs (clinic_id, sale_id, action, meta_json, actor_user_id, ip_address, created_at)
SELECT @clinic_id, @sale1, 'paid', JSON_OBJECT('amount', 350.00, 'method', 'credit_card'), @user_finance, '127.0.0.1', NOW()
WHERE @sale1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM sale_logs l
      WHERE l.clinic_id=@clinic_id AND l.sale_id=@sale1 AND l.action='paid'
      LIMIT 1
  );

INSERT INTO materials (clinic_id, name, category, unit, stock_current, stock_minimum, unit_cost, validity_date, status, created_at)
SELECT @clinic_id, 'Gel Condutor', 'consumível', 'un', 25.000, 5.000, 8.50, DATE_ADD(CURDATE(), INTERVAL 180 DAY), 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM materials m WHERE m.clinic_id=@clinic_id AND m.name='Gel Condutor' AND m.deleted_at IS NULL LIMIT 1
);
SET @mat1 = (SELECT id FROM materials m WHERE m.clinic_id=@clinic_id AND m.name='Gel Condutor' AND m.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO materials (clinic_id, name, category, unit, stock_current, stock_minimum, unit_cost, validity_date, status, created_at)
SELECT @clinic_id, 'Luvas', 'EPI', 'cx', 10.000, 2.000, 35.00, DATE_ADD(CURDATE(), INTERVAL 365 DAY), 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM materials m WHERE m.clinic_id=@clinic_id AND m.name='Luvas' AND m.deleted_at IS NULL LIMIT 1
);
SET @mat2 = (SELECT id FROM materials m WHERE m.clinic_id=@clinic_id AND m.name='Luvas' AND m.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO service_material_defaults (clinic_id, service_id, material_id, quantity_per_session, created_at)
SELECT @clinic_id, @svc2, @mat1, 1.000, NOW()
WHERE @svc2 IS NOT NULL AND @mat1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM service_material_defaults smd
      WHERE smd.clinic_id=@clinic_id AND smd.service_id=@svc2 AND smd.material_id=@mat1 AND smd.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO stock_movements (clinic_id, material_id, type, quantity, reference_type, reference_id, loss_reason, unit_cost_snapshot, total_cost_snapshot, notes, user_id, created_at)
SELECT @clinic_id, @mat1, 'exit', 1.000, 'session', NULL, NULL, 8.50, 8.50, 'Baixa demo: sessão laser', @user_prof1, NOW()
WHERE @mat1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM stock_movements sm
      WHERE sm.clinic_id=@clinic_id AND sm.material_id=@mat1 AND sm.type='exit' AND sm.notes='Baixa demo: sessão laser'
      LIMIT 1
  );

INSERT INTO appointment_materials_used (clinic_id, appointment_id, material_id, quantity, note, created_by_user_id, created_at)
SELECT @clinic_id,
       (SELECT id FROM appointments a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.status='done' AND a.deleted_at IS NULL ORDER BY id DESC LIMIT 1),
       @mat1, 1.000, 'Uso em consulta (demo)', @user_prof1, NOW()
WHERE @mat1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM appointment_materials_used u
      WHERE u.clinic_id=@clinic_id
        AND u.appointment_id=(SELECT id FROM appointments a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.status='done' AND a.deleted_at IS NULL ORDER BY id DESC LIMIT 1)
        AND u.material_id=@mat1
        AND u.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO stock_inventories (clinic_id, status, notes, created_by_user_id, confirmed_by_user_id, confirmed_at, created_at)
SELECT @clinic_id, 'confirmed', 'Inventário demo', @user_admin, @user_admin, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM stock_inventories i
    WHERE i.clinic_id=@clinic_id AND i.notes='Inventário demo' AND i.deleted_at IS NULL
    LIMIT 1
);
SET @inv1 = (SELECT id FROM stock_inventories i WHERE i.clinic_id=@clinic_id AND i.notes='Inventário demo' AND i.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO stock_inventory_items (clinic_id, inventory_id, material_id, qty_system_snapshot, qty_counted, qty_delta, unit_cost_snapshot, total_cost_delta_snapshot, created_at)
SELECT @clinic_id, @inv1, @mat1, 25.000, 24.000, -1.000, 8.50, -8.50, NOW()
WHERE @inv1 IS NOT NULL AND @mat1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM stock_inventory_items it
      WHERE it.clinic_id=@clinic_id AND it.inventory_id=@inv1 AND it.material_id=@mat1 AND it.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO security_rate_limits (scope, key_hash, window_start, window_seconds, hits, blocked_until, created_at, updated_at)
SELECT 'login', SHA2('demo-key', 256), DATE_SUB(NOW(), INTERVAL 10 MINUTE), 600, 3, NULL, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM security_rate_limits s WHERE s.scope='login' AND s.key_hash=SHA2('demo-key', 256) LIMIT 1);

INSERT INTO security_incidents (clinic_id, severity, status, title, description, detected_at, resolved_at, reported_by_user_id, assigned_to_user_id, corrective_action, created_at)
SELECT @clinic_id, 'low', 'resolved', 'Tentativa de acesso inválido (demo)', 'Incidente demo para tela de compliance.', NOW(), NOW(), @user_admin, @user_admin, 'Monitorar e reforçar senha forte.', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM security_incidents i
    WHERE i.clinic_id=@clinic_id AND i.title='Tentativa de acesso inválido (demo)'
    LIMIT 1
);

INSERT INTO system_metrics (clinic_id, metric, value, reference_date, created_at)
SELECT @clinic_id, 'active_users', 5, CURDATE(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM system_metrics m
    WHERE m.clinic_id=@clinic_id AND m.metric='active_users' AND m.reference_date=CURDATE()
    LIMIT 1
);

INSERT INTO event_logs (clinic_id, user_id, role, event, entity_type, entity_id, payload_json, ip, user_agent, created_at)
SELECT @clinic_id, @user_admin, 'admin', 'seed.demo', 'clinic', @clinic_id, JSON_OBJECT('ok', true), '127.0.0.1', 'seed', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM event_logs e
    WHERE e.clinic_id=@clinic_id AND e.event='seed.demo'
    LIMIT 1
);

INSERT INTO audit_logs (clinic_id, user_id, action, meta_json, ip_address, created_at, updated_at)
SELECT @clinic_id, @user_admin, 'seed.demo', JSON_OBJECT('clinic_id', @clinic_id), '127.0.0.1', NOW(), NULL
WHERE NOT EXISTS (
    SELECT 1 FROM audit_logs a
    WHERE a.clinic_id=@clinic_id AND a.action='seed.demo'
    LIMIT 1
);

INSERT INTO performance_logs (endpoint, method, response_time_ms, status_code, clinic_id, created_at)
SELECT '/dashboard', 'GET', 120, 200, @clinic_id, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM performance_logs p
    WHERE p.endpoint='/dashboard' AND p.clinic_id=@clinic_id
    LIMIT 1
);

INSERT INTO alert_rules (scope, clinic_id, user_id, metric, operator, threshold, window_days, action, channel, enabled, created_at)
SELECT 'clinic', @clinic_id, @user_owner, 'error_rate', '>', 10, 7, 'notify', 'email', 1, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM alert_rules a
    WHERE a.scope='clinic' AND a.clinic_id=@clinic_id AND a.metric='error_rate'
    LIMIT 1
);

INSERT INTO bi_snapshots (clinic_id, metric_key, period_start, period_end, data_json, computed_by_user_id, computed_at, created_at)
SELECT @clinic_id, 'revenue', DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE(), JSON_OBJECT('total', 350.00), @user_admin, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM bi_snapshots b
    WHERE b.clinic_id=@clinic_id AND b.metric_key='revenue' AND b.period_start=DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND b.period_end=CURDATE()
    LIMIT 1
);

INSERT INTO data_versions (clinic_id, entity_type, entity_id, action, snapshot_json, snapshot_hash, created_by_user_id, ip_address, user_agent, occurred_at, created_at)
SELECT @clinic_id, 'patients', @pat1, 'update', JSON_OBJECT('name','Mariana Souza'), SHA2(CONCAT('dv-', @clinic_id, '-patients-', @pat1), 256), @user_admin, '127.0.0.1', 'seed', NOW(), NOW()
WHERE @pat1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM data_versions d
      WHERE d.clinic_id=@clinic_id AND d.entity_type='patients' AND d.entity_id=@pat1
      LIMIT 1
  );

INSERT INTO data_exports (clinic_id, user_id, action, entity_type, entity_id, format, filename, meta_json, ip_address, user_agent, created_at)
SELECT @clinic_id, @user_admin, 'lgpd_export', 'patient', @pat1, 'zip', 'export_demo.zip', JSON_OBJECT('demo', 1), '127.0.0.1', 'seed', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM data_exports e
    WHERE e.clinic_id=@clinic_id AND e.action='lgpd_export' AND e.entity_type='patient' AND e.entity_id=@pat1 AND e.deleted_at IS NULL
    LIMIT 1
);

INSERT INTO google_oauth_tokens (clinic_id, user_id, provider, scopes, access_token, refresh_token_encrypted, expires_at, calendar_id, last_error, created_at)
SELECT @clinic_id, @user_admin, 'google', 'calendar', 'demo_access', 'demo_refresh', DATE_ADD(NOW(), INTERVAL 1 DAY), 'primary', NULL, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM google_oauth_tokens t
    WHERE t.clinic_id=@clinic_id AND t.user_id=@user_admin AND t.provider='google' AND t.revoked_at IS NULL
    LIMIT 1
);
SET @g_token = (SELECT id FROM google_oauth_tokens t WHERE t.clinic_id=@clinic_id AND t.user_id=@user_admin AND t.provider='google' AND t.revoked_at IS NULL LIMIT 1);

INSERT INTO google_calendar_appointment_events (clinic_id, appointment_id, token_id, google_event_id, google_calendar_id, last_synced_at, last_error, created_at)
SELECT @clinic_id, @appt_confirmed, @g_token, 'evt_demo_001', 'primary', NOW(), NULL, NOW()
WHERE @appt_confirmed IS NOT NULL AND @g_token IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM google_calendar_appointment_events e
      WHERE e.clinic_id=@clinic_id AND e.appointment_id=@appt_confirmed AND e.deleted_at IS NULL
      LIMIT 1
  );

INSERT INTO google_calendar_sync_logs (clinic_id, user_id, token_id, appointment_id, action, status, message, meta_json, created_at)
SELECT @clinic_id, @user_admin, @g_token, @appt_confirmed, 'sync', 'success', 'Sincronizado (demo)', JSON_OBJECT('demo', 1), NOW()
WHERE @appt_confirmed IS NOT NULL AND @g_token IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM google_calendar_sync_logs l
      WHERE l.clinic_id=@clinic_id AND l.appointment_id=@appt_confirmed AND l.action='sync'
      LIMIT 1
  );

INSERT INTO user_permissions_override (clinic_id, user_id, permission_id, effect, constraints_json, created_at)
SELECT @clinic_id, @user_finance,
       (SELECT id FROM permissions p WHERE p.code='finance.sales.read' LIMIT 1),
       'allow', NULL, NOW()
WHERE EXISTS (SELECT 1 FROM permissions p WHERE p.code='finance.sales.read')
  AND NOT EXISTS (
      SELECT 1 FROM user_permissions_override u
      WHERE u.clinic_id=@clinic_id AND u.user_id=@user_finance
      LIMIT 1
  );

INSERT INTO procedure_performed (clinic_id, appointment_id, patient_id, professional_id, service_id, procedure_id, real_started_at, real_ended_at, real_duration_minutes, stock_total_cost_snapshot, stock_movement_ids_json, financial_entry_id, note, created_by_user_id, created_at)
SELECT @clinic_id,
       (SELECT id FROM appointments a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.status='done' AND a.deleted_at IS NULL ORDER BY id DESC LIMIT 1),
       @pat2, @prof2, @svc2, @procedure_laser,
       DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 5 DAY), INTERVAL 60 MINUTE), 60,
       8.50, JSON_ARRAY(),
       (SELECT id FROM financial_entries fe WHERE fe.clinic_id=@clinic_id AND fe.sale_id=@sale1 AND fe.deleted_at IS NULL ORDER BY id DESC LIMIT 1),
       'Procedimento realizado (demo)', @user_prof1, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM procedure_performed pp
    WHERE pp.clinic_id=@clinic_id AND pp.appointment_id=(SELECT id FROM appointments a WHERE a.clinic_id=@clinic_id AND a.patient_id=@pat2 AND a.status='done' AND a.deleted_at IS NULL ORDER BY id DESC LIMIT 1)
      AND pp.deleted_at IS NULL
    LIMIT 1
);

SET @pp1 = (SELECT id FROM procedure_performed pp WHERE pp.clinic_id=@clinic_id AND pp.patient_id=@pat2 AND pp.deleted_at IS NULL ORDER BY id DESC LIMIT 1);

INSERT INTO procedure_performed_materials (clinic_id, performed_id, material_id, quantity, note, created_at)
SELECT @clinic_id, @pp1, @mat1, 1.000, 'Uso material (demo)', NOW()
WHERE @pp1 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM procedure_performed_materials m
      WHERE m.clinic_id=@clinic_id AND m.performed_id=@pp1 AND m.material_id=@mat1 AND m.deleted_at IS NULL
      LIMIT 1
  );

SET @plan_basic = COALESCE(
    (SELECT id FROM saas_plans WHERE code='basic' LIMIT 1),
    (SELECT id FROM saas_plans ORDER BY price_cents ASC LIMIT 1)
);

INSERT INTO clinic_subscriptions (clinic_id, plan_id, status, trial_ends_at, current_period_start, current_period_end, gateway_provider, asaas_customer_id, asaas_subscription_id, created_at)
SELECT @clinic_id, @plan_basic, 'active', NULL,
       DATE_SUB(NOW(), INTERVAL 10 DAY),
       DATE_ADD(NOW(), INTERVAL 20 DAY),
       'asaas', 'cus_demo_001', 'sub_demo_001', NOW()
WHERE NOT EXISTS (SELECT 1 FROM clinic_subscriptions cs WHERE cs.clinic_id=@clinic_id LIMIT 1);

UPDATE clinic_subscriptions
   SET pending_plan_id = COALESCE(pending_plan_id, @plan_basic),
       pending_plan_effective_at = COALESCE(pending_plan_effective_at, DATE_ADD(NOW(), INTERVAL 30 DAY)),
       pending_upgrade_plan_id = COALESCE(pending_upgrade_plan_id, NULL),
       pending_upgrade_payment_id = COALESCE(pending_upgrade_payment_id, NULL)
 WHERE clinic_id = @clinic_id;

INSERT INTO users (clinic_id, name, email, password_hash, is_super_admin, status, created_at)
VALUES (NULL, 'Super Admin', 'superadmin@lumiclinic.local', '$2y$12$2ARkjL8P.C.Yp.YchlLOtOGpqhudgr/N6PwnnWzKIkPQjv6Ypv8cu', 1, 'active', NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_super_admin = VALUES(is_super_admin),
    status = VALUES(status),
    updated_at = NOW();

COMMIT;
