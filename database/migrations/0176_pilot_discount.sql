-- Migration: 0176_pilot_discount
-- clinics.is_pilot: clinica participante do programa piloto.
-- clinic_subscriptions.pilot_discount_*: snapshot do desconto aplicado na marcacao.
--   pilot_discount_plan_id: plano que recebeu o beneficio (primeiro plano).
--   pilot_discount_forfeited: 1 = beneficio perdido definitivamente (troca de plano).
SET NAMES utf8mb4;
SET time_zone = '+00:00';
ALTER TABLE clinics
    ADD COLUMN is_pilot TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE clinic_subscriptions
    ADD COLUMN pilot_discount_percent DECIMAL(5,2) NULL,
    ADD COLUMN pilot_discount_starts_at DATETIME NULL,
    ADD COLUMN pilot_discount_ends_at DATETIME NULL,
    ADD COLUMN pilot_discount_plan_id BIGINT UNSIGNED NULL,
    ADD COLUMN pilot_discount_forfeited TINYINT(1) NOT NULL DEFAULT 0;
