-- Migration: 0177_pilot_discount_duration
-- Complementa a 0176_pilot_discount.
-- pilot_discount_duration_value/unit: duracao definida no momento da marcacao (exibida para a clinica).
--   unit: hours | days | months | years
-- pilot_discount_gateway_state: valor que o gateway esta cobrando hoje para a clinica piloto.
--   'discount' = com desconto, 'full' = valor cheio, NULL = ainda nao sincronizado.
--   A rotina do cron compara com a situacao atual (ativo, vencido, perdido) e corrige o valor no gateway.
SET NAMES utf8mb4;
ALTER TABLE clinic_subscriptions
    ADD COLUMN pilot_discount_duration_value INT UNSIGNED NULL,
    ADD COLUMN pilot_discount_duration_unit VARCHAR(10) NULL,
    ADD COLUMN pilot_discount_gateway_state VARCHAR(10) NULL;
