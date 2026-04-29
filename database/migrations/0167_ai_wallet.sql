-- Migration: 0167_ai_wallet
-- Carteira de IA do superadmin e ledger de transações
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS ai_wallet (
    id                          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    balance_brl                 DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    asaas_customer_id           VARCHAR(100)  NULL,
    asaas_card_token            VARCHAR(255)  NULL,
    asaas_card_last4            VARCHAR(4)    NULL,
    auto_recharge_enabled       TINYINT(1)    NOT NULL DEFAULT 0,
    auto_recharge_threshold_brl DECIMAL(10,2) NOT NULL DEFAULT 10.00,
    auto_recharge_amount_brl    DECIMAL(10,2) NOT NULL DEFAULT 50.00,
    created_at                  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_wallet_transactions (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    type              ENUM('debit','credit','charge_pending','manual_credit') NOT NULL,
    amount_brl        DECIMAL(10,4) NOT NULL,
    balance_after_brl DECIMAL(10,4) NOT NULL,
    description       VARCHAR(500)  NOT NULL DEFAULT '',
    clinic_id         INT UNSIGNED  NULL,
    audio_note_id     INT UNSIGNED  NULL,
    payment_id        VARCHAR(100)  NULL,
    duration_seconds  INT UNSIGNED  NULL,
    created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payment_id  (payment_id),
    KEY idx_type_date   (type, created_at),
    KEY idx_clinic_date (clinic_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
