-- Migration: 0123_appointments_checkin_and_started_at
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE appointments
    ADD COLUMN checked_in_at DATETIME NULL AFTER end_at,
    ADD COLUMN started_at DATETIME NULL AFTER checked_in_at,
    ADD KEY idx_appt_checked_in_at (clinic_id, checked_in_at),
    ADD KEY idx_appt_started_at (clinic_id, started_at);
