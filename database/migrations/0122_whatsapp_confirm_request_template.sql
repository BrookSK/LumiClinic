-- Migration: 0122_whatsapp_confirm_request_template
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO whatsapp_templates (clinic_id, code, name, body, status, created_at)
SELECT cs.clinic_id,
       'confirm_request',
       'Confirmação de consulta',
       'Olá {patient_name}!\n\nVocê confirma sua consulta em {date} às {time}?\n\nAbra aqui para Confirmar ou Cancelar:\n{confirm_url}\n\nClínica: {clinic_name}',
       'active',
       NOW()
FROM clinic_settings cs
LEFT JOIN whatsapp_templates t
  ON t.clinic_id = cs.clinic_id
 AND t.code = 'confirm_request'
 AND t.deleted_at IS NULL
WHERE cs.deleted_at IS NULL
  AND t.id IS NULL;
