-- Migration: 0018_rbac_defaults_table_and_seed
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS rbac_role_permission_defaults (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    role_code VARCHAR(64) NOT NULL,
    permission_code VARCHAR(128) NOT NULL,
    effect ENUM('allow','deny') NOT NULL DEFAULT 'allow',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_rbac_defaults_role_perm (role_code, permission_code),
    KEY idx_rbac_defaults_role (role_code),
    KEY idx_rbac_defaults_perm (permission_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Defaults mínimos (enterprise): owner começa com tudo do core + rbac.manage
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'owner'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND d.id IS NULL;

-- Admin (gerente): tudo do core exceto gestão de permissões (rbac.manage)
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'admin'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code <> 'rbac.manage'
  AND d.id IS NULL;

-- Financeiro: padrão conservador (por enquanto sem módulo financeiro implementado)
-- Mantém apenas dashboard (implícito) e leitura de clínica se necessário
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'finance', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'finance'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code IN ('clinics.read')
  AND d.id IS NULL;

-- Profissional: padrão conservador
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'professional', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'professional'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code IN ('clinics.read')
  AND d.id IS NULL;

-- Recepção: padrão conservador
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'reception', p.code, 'allow', NOW()
FROM permissions p
LEFT JOIN rbac_role_permission_defaults d
       ON d.role_code = 'reception'
      AND d.permission_code = p.code
WHERE p.deleted_at IS NULL
  AND p.code IN ('clinics.read','users.read')
  AND d.id IS NULL;
