-- Migration: 0070_bi_snapshots
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS bi_snapshots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    metric_key VARCHAR(64) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,

    data_json JSON NOT NULL,

    computed_by_user_id BIGINT UNSIGNED NULL,
    computed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_bi_snapshot (clinic_id, metric_key, period_start, period_end),
    KEY idx_bi_metric (clinic_id, metric_key),
    KEY idx_bi_computed_at (computed_at),

    CONSTRAINT fk_bi_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_bi_user FOREIGN KEY (computed_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'bi', 'read', 'bi.read', 'Ver dashboards executivos (BI)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'bi.read');

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'bi', 'refresh', 'bi.refresh', 'Atualizar snapshots (BI)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'bi.refresh');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', p.code, 'allow', NOW()
FROM permissions p
WHERE p.code IN ('bi.read','bi.refresh')
  AND NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='owner' AND d.permission_code=p.code);

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', p.code, 'allow', NOW()
FROM permissions p
WHERE p.code IN ('bi.read','bi.refresh')
  AND NOT EXISTS (SELECT 1 FROM rbac_role_permission_defaults d WHERE d.role_code='admin' AND d.permission_code=p.code);
