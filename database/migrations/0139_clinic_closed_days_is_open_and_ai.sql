-- Migration: 0139_clinic_closed_days_is_open_and_ai
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- clinic_closed_days: add is_open flag (0=fechado, 1=aberto)
SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'clinic_closed_days'
      AND COLUMN_NAME = 'is_open'
);

SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE clinic_closed_days ADD COLUMN is_open TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER reason',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'clinic_closed_days'
      AND INDEX_NAME = 'idx_ccd_clinic_open'
);

SET @sql := IF(
    @idx_exists = 0,
    'ALTER TABLE clinic_closed_days ADD KEY idx_ccd_clinic_open (clinic_id, is_open, closed_date)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
