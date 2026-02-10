-- Migration: 0088_stock_material_categories_and_units_seed
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Seed de Unidades (por clínica) - idempotente
INSERT INTO material_units (clinic_id, code, name, status, created_at)
SELECT c.id, v.code, v.name, 'active', NOW()
FROM clinics c
JOIN (
    SELECT 'un' AS code, 'Unidade' AS name
    UNION ALL SELECT 'ml', 'Mililitro'
    UNION ALL SELECT 'l',  'Litro'
    UNION ALL SELECT 'g',  'Grama'
    UNION ALL SELECT 'kg', 'Quilograma'
    UNION ALL SELECT 'cx', 'Caixa'
    UNION ALL SELECT 'pct','Pacote'
) v
WHERE NOT EXISTS (
    SELECT 1
    FROM material_units mu
    WHERE mu.clinic_id = c.id
      AND mu.code = v.code
      AND mu.deleted_at IS NULL
);

-- Seed de Categorias (por clínica) - idempotente
INSERT INTO material_categories (clinic_id, name, status, created_at)
SELECT c.id, v.name, 'active', NOW()
FROM clinics c
JOIN (
    SELECT 'Geral' AS name
    UNION ALL SELECT 'Descartáveis'
    UNION ALL SELECT 'Cosméticos'
    UNION ALL SELECT 'Limpeza'
    UNION ALL SELECT 'Medicamentos'
) v
WHERE NOT EXISTS (
    SELECT 1
    FROM material_categories mc
    WHERE mc.clinic_id = c.id
      AND mc.name = v.name
      AND mc.deleted_at IS NULL
);
