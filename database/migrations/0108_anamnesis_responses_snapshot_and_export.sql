-- Migration: 0108_anamnesis_responses_snapshot_and_export
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Snapshot imutável do template/campos no momento do preenchimento (versionamento)
ALTER TABLE anamnesis_responses
    ADD COLUMN template_name_snapshot VARCHAR(190) NULL AFTER template_id,
    ADD COLUMN template_updated_at_snapshot DATETIME NULL AFTER template_name_snapshot,
    ADD COLUMN fields_snapshot_json JSON NULL AFTER template_updated_at_snapshot;

ALTER TABLE anamnesis_responses
    ADD KEY idx_anamnesis_responses_template_patient (clinic_id, template_id, patient_id);
