-- Migration: 0069_security_incidents
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS security_incidents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    severity VARCHAR(16) NOT NULL DEFAULT 'medium', -- low|medium|high|critical
    status VARCHAR(16) NOT NULL DEFAULT 'open', -- open|investigating|contained|resolved

    title VARCHAR(190) NOT NULL,
    description TEXT NULL,

    detected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,

    reported_by_user_id BIGINT UNSIGNED NULL,
    assigned_to_user_id BIGINT UNSIGNED NULL,

    corrective_action TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_si_clinic_status (clinic_id, status),
    KEY idx_si_clinic_severity (clinic_id, severity),
    KEY idx_si_detected (detected_at),

    CONSTRAINT fk_si_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_si_reported_by FOREIGN KEY (reported_by_user_id) REFERENCES users (id),
    CONSTRAINT fk_si_assigned_to FOREIGN KEY (assigned_to_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissões
INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'compliance', 'read', 'compliance.incidents.read', 'Ver incidentes de segurança', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'compliance.incidents.read');

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'compliance', 'create', 'compliance.incidents.create', 'Registrar incidentes de segurança', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'compliance.incidents.create');

INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
SELECT NULL, 'compliance', 'update', 'compliance.incidents.update', 'Atualizar/encerrar incidentes de segurança', NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'compliance.incidents.update');

-- Defaults para owner/admin
INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'owner', p.code, 'allow', NOW()
FROM permissions p
WHERE p.code IN ('compliance.incidents.read','compliance.incidents.create','compliance.incidents.update')
  AND NOT EXISTS (
    SELECT 1 FROM rbac_role_permission_defaults d
    WHERE d.role_code='owner' AND d.permission_code=p.code
  );

INSERT INTO rbac_role_permission_defaults (role_code, permission_code, effect, created_at)
SELECT 'admin', p.code, 'allow', NOW()
FROM permissions p
WHERE p.code IN ('compliance.incidents.read','compliance.incidents.create','compliance.incidents.update')
  AND NOT EXISTS (
    SELECT 1 FROM rbac_role_permission_defaults d
    WHERE d.role_code='admin' AND d.permission_code=p.code
  );
