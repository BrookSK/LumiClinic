-- Migration: 0149_whatsapp_template_anamnesis
-- Cria template padrão de envio de anamnese via WhatsApp para cada clínica existente
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO whatsapp_templates (clinic_id, code, name, body, status, created_at)
SELECT
    c.id,
    'anamnesis_request',
    'Envio de Anamnese',
    'Olá {patient_name}! 👋\n\nA clínica {clinic_name} solicita que você preencha sua anamnese antes da consulta.\n\nAcesse o link abaixo para preencher:\n{link}\n\nObrigado!',
    'active',
    NOW()
FROM clinics c
WHERE c.deleted_at IS NULL
  AND NOT EXISTS (
    SELECT 1 FROM whatsapp_templates wt
    WHERE wt.clinic_id = c.id AND wt.code = 'anamnesis_request'
  );
