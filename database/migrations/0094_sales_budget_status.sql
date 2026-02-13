-- Migration: 0094_sales_budget_status
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE sales
    ADD COLUMN budget_status VARCHAR(32) NOT NULL DEFAULT 'draft' AFTER status,
    ADD KEY idx_sales_budget_status (budget_status);
