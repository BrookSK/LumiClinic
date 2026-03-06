-- Migration: 0133_google_calendar_oauth_and_sync
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS google_oauth_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,

    provider VARCHAR(32) NOT NULL DEFAULT 'google',

    scopes TEXT NULL,

    access_token TEXT NULL,
    refresh_token_encrypted TEXT NULL,
    expires_at DATETIME NULL,

    calendar_id VARCHAR(190) NULL,

    last_error TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_google_oauth_tokens_clinic_user_provider (clinic_id, user_id, provider),
    KEY idx_google_oauth_tokens_revoked (revoked_at),

    CONSTRAINT fk_google_oauth_tokens_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_google_oauth_tokens_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS google_calendar_appointment_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    appointment_id BIGINT UNSIGNED NOT NULL,
    token_id BIGINT UNSIGNED NOT NULL,

    google_event_id VARCHAR(190) NULL,
    google_calendar_id VARCHAR(190) NULL,

    last_synced_at DATETIME NULL,
    last_error TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_google_calendar_appt (clinic_id, appointment_id),
    KEY idx_google_calendar_appt_token (token_id),
    KEY idx_google_calendar_appt_deleted_at (deleted_at),

    CONSTRAINT fk_google_calendar_appt_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_google_calendar_appt_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id),
    CONSTRAINT fk_google_calendar_appt_token FOREIGN KEY (token_id) REFERENCES google_oauth_tokens (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS google_calendar_sync_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,

    token_id BIGINT UNSIGNED NULL,
    appointment_id BIGINT UNSIGNED NULL,

    action VARCHAR(64) NOT NULL,
    status VARCHAR(32) NOT NULL,
    message TEXT NULL,
    meta_json JSON NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_gcal_sync_logs_clinic_created (clinic_id, created_at),
    KEY idx_gcal_sync_logs_appt (appointment_id, id),

    CONSTRAINT fk_gcal_sync_logs_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_gcal_sync_logs_user FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_gcal_sync_logs_token FOREIGN KEY (token_id) REFERENCES google_oauth_tokens (id),
    CONSTRAINT fk_gcal_sync_logs_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
