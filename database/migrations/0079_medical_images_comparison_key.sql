SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE medical_images
    ADD COLUMN comparison_key VARCHAR(64) NULL AFTER kind,
    ADD KEY idx_medical_images_comparison (clinic_id, patient_id, comparison_key);
