-- Migration: 0071_compliance_certifications
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS compliance_policies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    code VARCHAR(64) NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,

    status VARCHAR(16) NOT NULL DEFAULT 'draft',
    version INT UNSIGNED NOT NULL DEFAULT 1,

    owner_user_id BIGINT UNSIGNED NULL,

    reviewed_at DATETIME NULL,
    next_review_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_cp (clinic_id, code),
    KEY idx_cp_clinic_status (clinic_id, status),
    KEY idx_cp_owner (clinic_id, owner_user_id),

    CONSTRAINT fk_cp_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_cp_owner FOREIGN KEY (owner_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS compliance_controls (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    policy_id BIGINT UNSIGNED NULL,

    code VARCHAR(64) NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,

    status VARCHAR(16) NOT NULL DEFAULT 'planned',

    owner_user_id BIGINT UNSIGNED NULL,

    evidence_url VARCHAR(500) NULL,
    last_tested_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_cc (clinic_id, code),
    KEY idx_cc_clinic_status (clinic_id, status),
    KEY idx_cc_policy (clinic_id, policy_id),
    KEY idx_cc_owner (clinic_id, owner_user_id),

    CONSTRAINT fk_cc_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_cc_policy FOREIGN KEY (policy_id) REFERENCES compliance_policies (id),
    CONSTRAINT fk_cc_owner FOREIGN KEY (owner_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'compliance', 'read', 'compliance.policies.read', 'Ver políticas e controles (certificações)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'compliance.policies.read');

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'compliance', 'create', 'compliance.policies.create', 'Cadastrar políticas (certificações)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'compliance.policies.create');

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'compliance', 'update', 'compliance.policies.update', 'Atualizar políticas (certificações)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'compliance.policies.update');

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'compliance', 'create', 'compliance.controls.create', 'Cadastrar controles (certificações)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'compliance.controls.create');

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'compliance', 'update', 'compliance.controls.update', 'Atualizar controles e evidências (certificações)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'compliance.controls.update');

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', p.code, 'allow', NOW()
FROM permissions p
WHERE p.code IN ('compliance.policies.read','compliance.policies.create','compliance.policies.update','compliance.controls.create','compliance.controls.update')
  AND NOT EXISTS (
    SELECT 1 FROM rbac_role_permission_defaults d
    WHERE d.role_code='owner' AND d.permission_code=p.code
  );

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', p.code, 'allow', NOW()
FROM permissions p
WHERE p.code IN ('compliance.policies.read','compliance.policies.create','compliance.policies.update','compliance.controls.create','compliance.controls.update')
  AND NOT EXISTS (
    SELECT 1 FROM rbac_role_permission_defaults d
    WHERE d.role_code='admin' AND d.permission_code=p.code
  );
