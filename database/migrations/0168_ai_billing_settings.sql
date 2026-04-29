-- Migration: 0168_ai_billing_settings
-- Configurações do desenvolvedor para faturamento de IA
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS ai_billing_settings (
    id                        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    asaas_api_key_encrypted   TEXT          NULL,
    openai_api_key_encrypted  TEXT          NULL,
    price_per_minute_brl      DECIMAL(10,4) NOT NULL DEFAULT 0.0910,
    cost_per_minute_brl       DECIMAL(10,4) NOT NULL DEFAULT 0.0350,
    dev_password_hash         VARCHAR(255)  NULL,
    created_at                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
