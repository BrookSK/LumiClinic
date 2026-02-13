-- Migration: 0054_clinic_settings_week_range
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE clinic_settings
    ADD COLUMN week_start_weekday TINYINT UNSIGNED NULL AFTER language,
    ADD COLUMN week_end_weekday TINYINT UNSIGNED NULL AFTER week_start_weekday;

UPDATE clinic_settings
   SET week_start_weekday = 1,
       week_end_weekday = 0
 WHERE (week_start_weekday IS NULL OR week_end_weekday IS NULL)
   AND deleted_at IS NULL;
