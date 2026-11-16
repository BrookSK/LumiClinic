-- Migration: 0175_clinic_logo_and_budget_payment_methods
-- clinics.logo_path: logo da clinica (storage privado).
-- sales.print_payment_methods: formas de pagamento exibidas no orcamento impresso.
--   NULL = nunca alterado (exibe todas); '' = nenhuma; 'pix,card,boleto,cash' = selecionadas.
SET NAMES utf8mb4;
SET time_zone = '+00:00';
ALTER TABLE clinics
    ADD COLUMN logo_path VARCHAR(500) NULL;
ALTER TABLE sales
    ADD COLUMN print_payment_methods VARCHAR(100) NULL;
