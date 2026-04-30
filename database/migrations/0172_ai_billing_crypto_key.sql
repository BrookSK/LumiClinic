-- Migration: 0172_ai_billing_crypto_key
-- Adiciona coluna para armazenar a chave de criptografia do sistema de IA
-- Auto-gerada na primeira vez que o SystemCryptoService é usado.
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE ai_billing_settings
    ADD COLUMN crypto_key VARCHAR(64) NULL AFTER id;
