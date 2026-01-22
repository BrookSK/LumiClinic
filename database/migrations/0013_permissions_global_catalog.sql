-- Migration: 0013_permissions_global_catalog
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Objetivo:
-- - Tornar permissions um catálogo GLOBAL (code único)
-- - Manter role_permissions apontando para um único permissions.id por code
-- - Remover duplicidades por clínica sem quebrar FKs

-- 1) Remover FK e UNIQUE antigo por clínica
ALTER TABLE permissions
    DROP FOREIGN KEY fk_permissions_clinic;

ALTER TABLE permissions
    DROP INDEX uq_permissions_clinic_code;

-- 2) Permitir NULL (catálogo global)
ALTER TABLE permissions
    MODIFY clinic_id BIGINT UNSIGNED NULL;

-- 3) Escolher 1 registro por code (canonical) e repontar role_permissions
CREATE TEMPORARY TABLE tmp_permissions_canonical AS
SELECT MIN(id) AS canonical_id, code
FROM permissions
WHERE deleted_at IS NULL
GROUP BY code;

UPDATE role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
INNER JOIN tmp_permissions_canonical c ON c.code = p.code
SET rp.permission_id = c.canonical_id
WHERE rp.deleted_at IS NULL;

-- 4) Remover duplicados (mantém apenas canonical)
DELETE p
FROM permissions p
LEFT JOIN tmp_permissions_canonical c ON c.canonical_id = p.id
WHERE c.canonical_id IS NULL;

-- 5) Global unique por code
ALTER TABLE permissions
    ADD UNIQUE KEY uq_permissions_code (code);

-- 6) Garantir clinic_id NULL em todo o catálogo
UPDATE permissions
SET clinic_id = NULL
WHERE clinic_id IS NOT NULL;
