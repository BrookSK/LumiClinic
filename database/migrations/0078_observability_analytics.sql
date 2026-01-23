SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS system_metrics (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NULL,
    metric VARCHAR(64) NOT NULL,
    value DECIMAL(18,4) NOT NULL DEFAULT 0,
    reference_date DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_system_metrics_clinic_metric_date (clinic_id, metric, reference_date),
    KEY idx_system_metrics_metric_date (metric, reference_date),
    CONSTRAINT fk_system_metrics_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    role VARCHAR(64) NULL,
    event VARCHAR(128) NOT NULL,
    entity_type VARCHAR(64) NULL,
    entity_id BIGINT UNSIGNED NULL,
    payload_json JSON NOT NULL,
    ip VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_event_logs_clinic_event_time (clinic_id, event, created_at),
    KEY idx_event_logs_user_time (user_id, created_at),
    KEY idx_event_logs_entity (entity_type, entity_id, created_at),
    CONSTRAINT fk_event_logs_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_event_logs_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS performance_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    endpoint VARCHAR(190) NOT NULL,
    method VARCHAR(16) NOT NULL,
    response_time_ms INT UNSIGNED NOT NULL,
    status_code INT UNSIGNED NOT NULL,
    clinic_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_performance_logs_time (created_at),
    KEY idx_performance_logs_endpoint_time (endpoint, created_at),
    KEY idx_performance_logs_clinic_time (clinic_id, created_at),
    CONSTRAINT fk_performance_logs_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alert_rules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scope VARCHAR(16) NOT NULL,
    clinic_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    metric VARCHAR(64) NOT NULL,
    operator VARCHAR(8) NOT NULL,
    threshold DECIMAL(18,4) NOT NULL,
    window_days INT UNSIGNED NOT NULL DEFAULT 7,
    action VARCHAR(32) NOT NULL,
    channel VARCHAR(32) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_alert_rules_scope_enabled (scope, enabled),
    KEY idx_alert_rules_clinic (clinic_id, enabled),
    KEY idx_alert_rules_user (user_id, enabled),
    CONSTRAINT fk_alert_rules_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_alert_rules_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
