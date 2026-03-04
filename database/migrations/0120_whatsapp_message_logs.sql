-- Migration: 0120_whatsapp_message_logs
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS whatsapp_message_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NULL,
    appointment_id BIGINT UNSIGNED NULL,

    template_code VARCHAR(64) NOT NULL,
    scheduled_for DATETIME NOT NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    sent_at DATETIME NULL,

    provider_message_id VARCHAR(190) NULL,

    payload_json JSON NULL,
    response_json JSON NULL,
    error_message TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_wa_msg_logs_clinic_appt_tpl (clinic_id, appointment_id, template_code),
    KEY idx_wa_msg_logs_clinic_sched (clinic_id, scheduled_for, status),
    KEY idx_wa_msg_logs_patient (clinic_id, patient_id),
    CONSTRAINT fk_wa_msg_logs_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO queue_jobs (clinic_id, queue, job_type, payload_json, status, attempts, max_attempts, run_at, created_at)
SELECT cs.clinic_id,
       'notifications',
       'whatsapp.reminders.reconcile',
       JSON_OBJECT('seed', 1),
       'pending',
       0,
       10,
       (NOW() + INTERVAL 1 MINUTE),
       NOW()
FROM clinic_settings cs
LEFT JOIN queue_jobs q
  ON q.clinic_id = cs.clinic_id
 AND q.job_type = 'whatsapp.reminders.reconcile'
 AND q.status IN ('pending', 'processing')
WHERE cs.deleted_at IS NULL
  AND q.id IS NULL;
