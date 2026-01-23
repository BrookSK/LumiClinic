-- Migration: 0051_stock_materials_and_movements
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS materials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(190) NOT NULL,
    category VARCHAR(64) NULL,
    unit VARCHAR(16) NOT NULL,

    stock_current DECIMAL(12,3) NOT NULL DEFAULT 0,
    stock_minimum DECIMAL(12,3) NOT NULL DEFAULT 0,

    unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    validity_date DATE NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_materials_clinic_id (clinic_id),
    KEY idx_materials_clinic_status (clinic_id, status),
    KEY idx_materials_clinic_name (clinic_id, name),
    KEY idx_materials_deleted_at (deleted_at),
    CONSTRAINT fk_materials_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    material_id BIGINT UNSIGNED NOT NULL,

    type VARCHAR(32) NOT NULL, -- entry|exit|adjustment|loss|expiration
    quantity DECIMAL(12,3) NOT NULL,

    reference_type VARCHAR(32) NULL, -- session|purchase|adjustment|loss
    reference_id BIGINT UNSIGNED NULL,

    loss_reason VARCHAR(32) NULL, -- expiration|breakage|contamination|operational_error

    unit_cost_snapshot DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_cost_snapshot DECIMAL(12,2) NOT NULL DEFAULT 0,

    notes VARCHAR(255) NULL,

    user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_stock_movements_clinic_id (clinic_id),
    KEY idx_stock_movements_material_id (material_id),
    KEY idx_stock_movements_clinic_material_created (clinic_id, material_id, created_at),
    KEY idx_stock_movements_reference (clinic_id, reference_type, reference_id),
    KEY idx_stock_movements_deleted_at (deleted_at),

    CONSTRAINT fk_stock_movements_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_stock_movements_material FOREIGN KEY (material_id) REFERENCES materials (id),
    CONSTRAINT fk_stock_movements_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Materiais padrão por procedimento/serviço (para baixa automática)
CREATE TABLE IF NOT EXISTS service_material_defaults (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    material_id BIGINT UNSIGNED NOT NULL,

    quantity_per_session DECIMAL(12,3) NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_service_material_defaults (clinic_id, service_id, material_id),
    KEY idx_smd_clinic_service (clinic_id, service_id),
    KEY idx_smd_clinic_material (clinic_id, material_id),
    KEY idx_smd_deleted_at (deleted_at),

    CONSTRAINT fk_smd_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_smd_service FOREIGN KEY (service_id) REFERENCES services (id),
    CONSTRAINT fk_smd_material FOREIGN KEY (material_id) REFERENCES materials (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
