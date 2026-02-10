-- Migration: 0089_stock_material_categories_and_units_status_ptbr
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Padronizar status em PT-BR (ativo) para evitar textos em inglês na UI

UPDATE material_categories
SET status = 'ativo'
WHERE status = 'active';

UPDATE material_units
SET status = 'ativo'
WHERE status = 'active';

ALTER TABLE material_categories
    MODIFY status VARCHAR(32) NOT NULL DEFAULT 'ativo';

ALTER TABLE material_units
    MODIFY status VARCHAR(32) NOT NULL DEFAULT 'ativo';
