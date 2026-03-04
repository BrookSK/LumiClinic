-- Migration: 0119_whatsapp_templates
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    code VARCHAR(64) NOT NULL,
    name VARCHAR(190) NOT NULL,
    body TEXT NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_whatsapp_templates_clinic_code (clinic_id, code),
    KEY idx_whatsapp_templates_clinic_id (clinic_id),
    KEY idx_whatsapp_templates_status (clinic_id, status),
    KEY idx_whatsapp_templates_deleted_at (deleted_at),
    CONSTRAINT fk_whatsapp_templates_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO whatsapp_templates (clinic_id, code, name, body, status, created_at)
SELECT cs.clinic_id,
       'reminder_24h',
       'Lembrete 24h',
       'Olá {patient_name}!\n\nLembrete: você tem um agendamento em {date} às {time}.\n\nClínica: {clinic_name}',
       'active',
       NOW()
FROM clinic_settings cs
LEFT JOIN whatsapp_templates t
  ON t.clinic_id = cs.clinic_id
 AND t.code = 'reminder_24h'
 AND t.deleted_at IS NULL
WHERE cs.deleted_at IS NULL
  AND t.id IS NULL;

INSERT INTO whatsapp_templates (clinic_id, code, name, body, status, created_at)
SELECT cs.clinic_id,
       'reminder_2h',
       'Lembrete 2h',
       'Olá {patient_name}!\n\nSeu agendamento é hoje em {date} às {time}.\n\nClínica: {clinic_name}',
       'active',
       NOW()
FROM clinic_settings cs
LEFT JOIN whatsapp_templates t
  ON t.clinic_id = cs.clinic_id
 AND t.code = 'reminder_2h'
 AND t.deleted_at IS NULL
WHERE cs.deleted_at IS NULL
  AND t.id IS NULL;
