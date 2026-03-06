-- Migration: 0129_marketing_automation
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS marketing_segments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(190) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',

    rules_json JSON NOT NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_marketing_segments_clinic_id (clinic_id),
    KEY idx_marketing_segments_clinic_status (clinic_id, status),
    KEY idx_marketing_segments_deleted_at (deleted_at),

    CONSTRAINT fk_marketing_segments_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_marketing_segments_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(190) NOT NULL,
    channel VARCHAR(32) NOT NULL, -- whatsapp|email

    segment_id BIGINT UNSIGNED NULL,

    whatsapp_template_code VARCHAR(64) NULL,
    email_subject VARCHAR(190) NULL,
    email_body TEXT NULL,

    click_url VARCHAR(500) NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'draft', -- draft|scheduled|running|paused|completed|cancelled

    scheduled_for DATETIME NULL,
    last_run_at DATETIME NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_marketing_campaigns_clinic_id (clinic_id),
    KEY idx_marketing_campaigns_clinic_status (clinic_id, status),
    KEY idx_marketing_campaigns_clinic_scheduled_for (clinic_id, scheduled_for),
    KEY idx_marketing_campaigns_deleted_at (deleted_at),

    CONSTRAINT fk_marketing_campaigns_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_marketing_campaigns_segment FOREIGN KEY (segment_id) REFERENCES marketing_segments (id),
    CONSTRAINT fk_marketing_campaigns_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketing_campaign_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    campaign_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    channel VARCHAR(32) NOT NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'queued', -- queued|processing|sent|failed|delivered|read|clicked|skipped|cancelled

    provider_message_id VARCHAR(190) NULL,

    scheduled_for DATETIME NULL,
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    read_at DATETIME NULL,
    clicked_at DATETIME NULL,

    click_token VARCHAR(64) NULL,
    click_url_snapshot VARCHAR(500) NULL,

    payload_json JSON NULL,
    response_json JSON NULL,
    error_message VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_marketing_campaign_message (clinic_id, campaign_id, patient_id),
    KEY idx_marketing_campaign_messages_clinic_id (clinic_id),
    KEY idx_marketing_campaign_messages_campaign_id (campaign_id),
    KEY idx_marketing_campaign_messages_patient_id (patient_id),
    KEY idx_marketing_campaign_messages_provider_id (provider_message_id),
    KEY idx_marketing_campaign_messages_click_token (click_token),
    KEY idx_marketing_campaign_messages_deleted_at (deleted_at),

    CONSTRAINT fk_marketing_campaign_messages_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_marketing_campaign_messages_campaign FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns (id),
    CONSTRAINT fk_marketing_campaign_messages_patient FOREIGN KEY (patient_id) REFERENCES patients (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
