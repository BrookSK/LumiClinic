-- Migration: 0107_consent_acceptances_snapshot_and_export
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Snapshot imutável do termo no momento do aceite (versionamento do conteúdo assinado)
ALTER TABLE consent_acceptances
    ADD COLUMN term_procedure_type_snapshot VARCHAR(190) NULL AFTER procedure_type,
    ADD COLUMN term_title_snapshot VARCHAR(190) NULL AFTER term_procedure_type_snapshot,
    ADD COLUMN term_body_snapshot MEDIUMTEXT NULL AFTER term_title_snapshot,
    ADD COLUMN term_updated_at_snapshot DATETIME NULL AFTER term_body_snapshot;

-- Index auxiliar para export/consulta por termo
ALTER TABLE consent_acceptances
    ADD KEY idx_consent_acceptances_term_patient (clinic_id, term_id, patient_id);
