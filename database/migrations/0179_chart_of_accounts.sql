-- Migration: 0179_chart_of_accounts
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Plano de contas por clínica (estrutura hierárquica: 1 ATIVO > 1.1 ATIVO CIRCULANTE > 1.1.1 Caixa > 1.1.1.01 Caixa Geral)
CREATE TABLE IF NOT EXISTS chart_accounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,

    code VARCHAR(32) NOT NULL,
    name VARCHAR(190) NOT NULL,

    -- asset | liability | equity | revenue | expense
    nature VARCHAR(16) NOT NULL,
    -- synthetic (grupo/totalizadora) | analytic (recebe lançamentos)
    account_type VARCHAR(16) NOT NULL DEFAULT 'analytic',

    -- forma de pagamento vinculada (pix, credit_card, debit_card, boleto, cash, transfer, check, other)
    payment_method VARCHAR(32) NULL,
    -- conta padrão para lançamentos sem classificação: revenue | expense
    default_for VARCHAR(16) NULL,

    -- código da conta no sistema do contador
    external_code VARCHAR(64) NULL,
    description VARCHAR(255) NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_chart_accounts_clinic_code (clinic_id, code),
    KEY idx_chart_accounts_clinic (clinic_id),
    KEY idx_chart_accounts_parent (parent_id),

    CONSTRAINT fk_chart_accounts_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_chart_accounts_parent FOREIGN KEY (parent_id) REFERENCES chart_accounts (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados contábeis no centro de custo
ALTER TABLE cost_centers
    ADD COLUMN code VARCHAR(32) NULL AFTER name,
    ADD COLUMN description VARCHAR(255) NULL AFTER code,
    ADD COLUMN chart_account_id BIGINT UNSIGNED NULL AFTER description,
    ADD KEY idx_cost_centers_chart_account (chart_account_id),
    ADD CONSTRAINT fk_cost_centers_chart_account FOREIGN KEY (chart_account_id) REFERENCES chart_accounts (id);
