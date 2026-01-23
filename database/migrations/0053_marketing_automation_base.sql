-- Migration: 0053_marketing_automation_base
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Templates (email/whatsapp/sms) - apenas estrutura
CREATE TABLE IF NOT EXISTS marketing_templates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    channel VARCHAR(32) NOT NULL, -- email|whatsapp|sms
    name VARCHAR(190) NOT NULL,

    subject VARCHAR(190) NULL,
    body TEXT NOT NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_mkt_tpl_clinic (clinic_id),
    KEY idx_mkt_tpl_channel (clinic_id, channel),
    KEY idx_mkt_tpl_status (clinic_id, status),
    KEY idx_mkt_tpl_deleted_at (deleted_at),

    CONSTRAINT fk_mkt_tpl_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_mkt_tpl_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Triggers (evento que gera elegibilidade para regra) - apenas estrutura
CREATE TABLE IF NOT EXISTS marketing_triggers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    code VARCHAR(64) NOT NULL, -- appointment_completed|birthday|no_show|reactivation
    name VARCHAR(190) NOT NULL,

    -- parâmetros do gatilho (ex: dias após, janela de horário, etc)
    config_json JSON NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_mkt_trg (clinic_id, code),
    KEY idx_mkt_trg_clinic (clinic_id),
    KEY idx_mkt_trg_status (clinic_id, status),
    KEY idx_mkt_trg_deleted_at (deleted_at),

    CONSTRAINT fk_mkt_trg_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_mkt_trg_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rules (condições + ação) - apenas estrutura
CREATE TABLE IF NOT EXISTS marketing_rules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(190) NOT NULL,
    description VARCHAR(255) NULL,

    trigger_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED NULL,

    -- filtros/condições da regra (ex: serviço, profissional, tags, valores, etc)
    conditions_json JSON NULL,

    -- ação de envio (delay, canal, etc)
    action_json JSON NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'draft', -- draft|active|paused

    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_mkt_rules_clinic (clinic_id),
    KEY idx_mkt_rules_trigger (clinic_id, trigger_id),
    KEY idx_mkt_rules_status (clinic_id, status),
    KEY idx_mkt_rules_deleted_at (deleted_at),

    CONSTRAINT fk_mkt_rules_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_mkt_rules_trigger FOREIGN KEY (trigger_id) REFERENCES marketing_triggers (id),
    CONSTRAINT fk_mkt_rules_template FOREIGN KEY (template_id) REFERENCES marketing_templates (id),
    CONSTRAINT fk_mkt_rules_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log de eventos/execução (sem disparo por enquanto, só estrutura)
CREATE TABLE IF NOT EXISTS marketing_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    event_code VARCHAR(64) NOT NULL,
    entity_type VARCHAR(32) NULL,
    entity_id BIGINT UNSIGNED NULL,

    payload_json JSON NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_mkt_evt_clinic (clinic_id),
    KEY idx_mkt_evt_code (clinic_id, event_code),
    KEY idx_mkt_evt_entity (clinic_id, entity_type, entity_id),

    CONSTRAINT fk_mkt_evt_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fila/saídas (não dispara, apenas estrutura para futuro worker)
CREATE TABLE IF NOT EXISTS marketing_outbox (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    rule_id BIGINT UNSIGNED NULL,
    template_id BIGINT UNSIGNED NULL,

    channel VARCHAR(32) NOT NULL,
    recipient VARCHAR(190) NOT NULL,

    subject VARCHAR(190) NULL,
    body TEXT NOT NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'pending', -- pending|sent|failed|cancelled
    scheduled_at DATETIME NULL,
    sent_at DATETIME NULL,

    error_message VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_mkt_out_clinic (clinic_id),
    KEY idx_mkt_out_status (clinic_id, status),
    KEY idx_mkt_out_scheduled (clinic_id, scheduled_at),
    KEY idx_mkt_out_rule (clinic_id, rule_id),

    CONSTRAINT fk_mkt_out_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_mkt_out_rule FOREIGN KEY (rule_id) REFERENCES marketing_rules (id),
    CONSTRAINT fk_mkt_out_template FOREIGN KEY (template_id) REFERENCES marketing_templates (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
