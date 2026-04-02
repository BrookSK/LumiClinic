-- Migration: 0152_audio_notes_duration
SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE medical_record_audio_notes
    ADD COLUMN IF NOT EXISTS duration_seconds INT UNSIGNED NULL AFTER size_bytes;
