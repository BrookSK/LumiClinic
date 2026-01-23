SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS saas_plans (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(64) NOT NULL,
    name VARCHAR(190) NOT NULL,
    price_cents INT UNSIGNED NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'BRL',
    interval_unit VARCHAR(16) NOT NULL DEFAULT 'month',
    interval_count INT UNSIGNED NOT NULL DEFAULT 1,
    trial_days INT UNSIGNED NOT NULL DEFAULT 0,
    limits_json JSON NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_saas_plans_code (code),
    KEY idx_saas_plans_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clinic_subscriptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'trial',
    trial_ends_at DATETIME NULL,
    current_period_start DATETIME NULL,
    current_period_end DATETIME NULL,
    cancel_at_period_end TINYINT(1) NOT NULL DEFAULT 0,

    past_due_since DATETIME NULL,

    gateway_provider VARCHAR(32) NULL,
    asaas_customer_id VARCHAR(190) NULL,
    asaas_subscription_id VARCHAR(190) NULL,
    mp_preapproval_id VARCHAR(190) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_clinic_subscriptions_clinic (clinic_id),
    KEY idx_clinic_subscriptions_status (status),
    KEY idx_clinic_subscriptions_plan (plan_id),

    CONSTRAINT fk_clinic_subscriptions_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_clinic_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES saas_plans (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS billing_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NULL,

    provider VARCHAR(32) NULL,
    event_type VARCHAR(64) NOT NULL,
    external_id VARCHAR(190) NULL,
    payload_json JSON NOT NULL,

    processed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_billing_events_clinic (clinic_id, id),
    KEY idx_billing_events_type (event_type, id),
    KEY idx_billing_events_processed (processed_at, id),
    UNIQUE KEY uq_billing_events_provider_external (provider, external_id),

    CONSTRAINT fk_billing_events_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
