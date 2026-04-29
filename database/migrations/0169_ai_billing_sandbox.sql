-- Migration: 0169_ai_billing_sandbox
-- Adiciona suporte a modo sandbox/produção para o Asaas de IA
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Adiciona coluna de modo (sandbox/production)
ALTER TABLE ai_billing_settings
    ADD COLUMN asaas_mode ENUM('sandbox','production') NOT NULL DEFAULT 'sandbox' AFTER asaas_api_key_encrypted;

-- Adiciona coluna para chave sandbox separada
ALTER TABLE ai_billing_settings
    ADD COLUMN asaas_sandbox_key_encrypted TEXT NULL AFTER asaas_mode;
