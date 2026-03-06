-- Migration: 0135_service_categories
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS service_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(190) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_service_categories_clinic (clinic_id),
    KEY idx_service_categories_deleted_at (deleted_at),
    UNIQUE KEY uniq_service_categories_clinic_name (clinic_id, name),
    CONSTRAINT fk_service_categories_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add category_id to services (idempotent)
SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'services'
      AND COLUMN_NAME = 'category_id'
);

SET @sql_add_col := IF(@col_exists = 0,
    'ALTER TABLE services ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER procedure_id',
    'SELECT 1'
);
PREPARE stmt_add_col FROM @sql_add_col;
EXECUTE stmt_add_col;
DEALLOCATE PREPARE stmt_add_col;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'services'
      AND INDEX_NAME = 'idx_services_category'
);

SET @sql_add_idx := IF(@idx_exists = 0,
    'ALTER TABLE services ADD KEY idx_services_category (category_id)',
    'SELECT 1'
);
PREPARE stmt_add_idx FROM @sql_add_idx;
EXECUTE stmt_add_idx;
DEALLOCATE PREPARE stmt_add_idx;

SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'services'
      AND CONSTRAINT_NAME = 'fk_services_category'
);

SET @sql_add_fk := IF(@fk_exists = 0,
    'ALTER TABLE services ADD CONSTRAINT fk_services_category FOREIGN KEY (category_id) REFERENCES service_categories (id)',
    'SELECT 1'
);
PREPARE stmt_add_fk FROM @sql_add_fk;
EXECUTE stmt_add_fk;
DEALLOCATE PREPARE stmt_add_fk;
