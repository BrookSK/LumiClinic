-- Migration: 0171_ai_billing_webhook_secrets
-- Adiciona webhook secrets separados para sandbox e produção do Asaas de IA
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE ai_billing_settings
    ADD COLUMN asaas_webhook_secret_sandbox_encrypted    TEXT NULL AFTER asaas_sandbox_key_encrypted,
    ADD COLUMN asaas_webhook_secret_production_encrypted TEXT NULL AFTER asaas_webhook_secret_sandbox_encrypted;
