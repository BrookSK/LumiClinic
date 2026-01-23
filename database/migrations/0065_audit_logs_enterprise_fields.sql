-- Migration: 0065_audit_logs_enterprise_fields
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Evolução do audit trail para nível enterprise.
ALTER TABLE audit_logs
    ADD COLUMN role_codes_json JSON NULL AFTER user_id,
    ADD COLUMN entity_type VARCHAR(64) NULL AFTER action,
    ADD COLUMN entity_id BIGINT UNSIGNED NULL AFTER entity_type,
    ADD COLUMN user_agent VARCHAR(255) NULL AFTER ip_address,
    ADD COLUMN occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER user_agent;

CREATE INDEX idx_audit_logs_entity ON audit_logs (entity_type, entity_id);
CREATE INDEX idx_audit_logs_occurred_at ON audit_logs (occurred_at);
