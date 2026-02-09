-- Migration: 0081_stock_role_permissions_softdelete_fix
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Fix: quando existe role_permissions (clinic_id, role_id, permission_id) soft-deletado,
-- o INSERT idempotente falha por causa do unique. Aqui reativamos e garantimos inserção.

-- Reativa permissões soft-deletadas (evita violação de unique)
UPDATE role_permissions rp
INNER JOIN roles r
        ON r.id = rp.role_id
       AND r.clinic_id = rp.clinic_id
       AND r.deleted_at IS NULL
INNER JOIN permissions p
        ON p.id = rp.permission_id
       AND p.deleted_at IS NULL
SET rp.deleted_at = NULL,
    rp.updated_at = NOW(),
    rp.effect = 'allow'
WHERE rp.deleted_at IS NOT NULL
  AND r.code IN ('owner','admin')
  AND p.code IN ('stock.materials.read','stock.materials.manage');

-- Insere somente quando não existir nenhuma linha (considera também deleted_at)
INSERT INTO role_permissions (clinic_id, role_id, permission_id, effect, created_at)
SELECT r.clinic_id, r.id, p.id, 'allow', NOW()
FROM roles r
INNER JOIN permissions p ON p.deleted_at IS NULL
WHERE r.deleted_at IS NULL
  AND r.code IN ('owner','admin')
  AND p.code IN ('stock.materials.read','stock.materials.manage')
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.clinic_id = r.clinic_id
        AND rp.role_id = r.id
        AND rp.permission_id = p.id
  );
