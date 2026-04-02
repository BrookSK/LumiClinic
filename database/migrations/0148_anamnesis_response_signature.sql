-- Migration: 0148_anamnesis_response_signature
-- Adiciona assinatura digital e snapshot do template nas respostas de anamnese
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE anamnesis_responses
    ADD COLUMN IF NOT EXISTS signature_data_url MEDIUMTEXT NULL AFTER answers_json,
    ADD COLUMN IF NOT EXISTS template_name_snapshot VARCHAR(190) NULL AFTER signature_data_url,
    ADD COLUMN IF NOT EXISTS signed_at DATETIME NULL AFTER template_name_snapshot;
