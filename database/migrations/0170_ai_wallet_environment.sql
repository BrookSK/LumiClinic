-- Migration: 0170_ai_wallet_environment
-- Separa carteira e transações por ambiente (sandbox/production)
-- Isso permite testar em sandbox sem contaminar o saldo de produção.
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Adiciona coluna de ambiente na carteira
-- Agora existem duas linhas: id=1 (sandbox) e id=2 (production)
ALTER TABLE ai_wallet
    ADD COLUMN environment ENUM('sandbox','production') NOT NULL DEFAULT 'sandbox' AFTER id;

-- Remove a constraint de singleton (id=1) se existir, pois agora teremos 2 linhas
-- (a tabela não tinha CHECK constraint explícita, apenas convenção de uso)

-- Renomeia a linha existente como sandbox e insere linha de produção
UPDATE ai_wallet SET environment = 'sandbox' WHERE id = 1;

INSERT INTO ai_wallet (id, environment, balance_brl, auto_recharge_enabled, auto_recharge_threshold_brl, auto_recharge_amount_brl)
VALUES (2, 'production', 0.0000, 0, 10.00, 50.00)
ON DUPLICATE KEY UPDATE environment = 'production';

-- Adiciona coluna de ambiente nas transações
ALTER TABLE ai_wallet_transactions
    ADD COLUMN environment ENUM('sandbox','production') NOT NULL DEFAULT 'sandbox' AFTER id;

-- Índice para filtrar por ambiente
ALTER TABLE ai_wallet_transactions
    ADD KEY idx_environment_date (environment, created_at);
