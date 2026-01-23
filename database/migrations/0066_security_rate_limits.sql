-- Migration: 0066_security_rate_limits
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Rate limiting / brute-force protection (persistente, multi-instância)
CREATE TABLE IF NOT EXISTS security_rate_limits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scope VARCHAR(64) NOT NULL,
    key_hash VARCHAR(64) NOT NULL,

    window_start DATETIME NOT NULL,
    window_seconds INT UNSIGNED NOT NULL,
    hits INT UNSIGNED NOT NULL DEFAULT 0,

    blocked_until DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_srl_scope_key (scope, key_hash),
    KEY idx_srl_scope (scope),
    KEY idx_srl_blocked (blocked_until),
    KEY idx_srl_window_start (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
