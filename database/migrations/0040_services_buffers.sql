-- Migration: 0040_services_buffers
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE services
    ADD COLUMN buffer_before_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER duration_minutes,
    ADD COLUMN buffer_after_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER buffer_before_minutes;

ALTER TABLE appointments
    ADD COLUMN buffer_before_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER end_at,
    ADD COLUMN buffer_after_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER buffer_before_minutes;
