-- Migration: 0128_stock_inventory
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS stock_inventories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'draft', -- draft|confirmed|cancelled

    notes VARCHAR(255) NULL,

    created_by_user_id BIGINT UNSIGNED NULL,
    confirmed_by_user_id BIGINT UNSIGNED NULL,

    confirmed_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_stock_inventories_clinic_id (clinic_id),
    KEY idx_stock_inventories_clinic_status (clinic_id, status),
    KEY idx_stock_inventories_deleted_at (deleted_at),

    CONSTRAINT fk_stock_inventories_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_stock_inventories_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id),
    CONSTRAINT fk_stock_inventories_confirmed_by FOREIGN KEY (confirmed_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_inventory_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    inventory_id BIGINT UNSIGNED NOT NULL,
    material_id BIGINT UNSIGNED NOT NULL,

    qty_system_snapshot DECIMAL(12,3) NOT NULL DEFAULT 0,
    qty_counted DECIMAL(12,3) NOT NULL DEFAULT 0,
    qty_delta DECIMAL(12,3) NOT NULL DEFAULT 0,

    unit_cost_snapshot DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_cost_delta_snapshot DECIMAL(12,2) NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_stock_inventory_items (clinic_id, inventory_id, material_id),
    KEY idx_stock_inventory_items_clinic_id (clinic_id),
    KEY idx_stock_inventory_items_inventory_id (inventory_id),
    KEY idx_stock_inventory_items_material_id (material_id),
    KEY idx_stock_inventory_items_deleted_at (deleted_at),

    CONSTRAINT fk_stock_inventory_items_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_stock_inventory_items_inventory FOREIGN KEY (inventory_id) REFERENCES stock_inventories (id),
    CONSTRAINT fk_stock_inventory_items_material FOREIGN KEY (material_id) REFERENCES materials (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
