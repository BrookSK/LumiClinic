-- Migration: 0130_marketing_automation_triggers
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

START TRANSACTION;

ALTER TABLE marketing_campaigns
    ADD COLUMN trigger_event VARCHAR(64) NULL AFTER segment_id,
    ADD COLUMN trigger_delay_minutes INT NULL AFTER trigger_event;

CREATE INDEX idx_marketing_campaigns_clinic_trigger
    ON marketing_campaigns (clinic_id, trigger_event, status);

COMMIT;
