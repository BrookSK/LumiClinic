-- Migration: 0173_patients_photo_path
-- Adiciona coluna para foto/avatar do paciente.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE patients
    ADD COLUMN photo_path VARCHAR(500) NULL AFTER notes;
