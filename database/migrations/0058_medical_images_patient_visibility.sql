-- Migration: 0058_medical_images_patient_visibility
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE medical_images
    ADD COLUMN patient_visibility_status VARCHAR(32) NOT NULL DEFAULT 'internal' AFTER size_bytes,
    ADD COLUMN approved_at DATETIME NULL AFTER patient_visibility_status,
    ADD COLUMN approved_by_user_id BIGINT UNSIGNED NULL AFTER approved_at,
    ADD COLUMN uploaded_by_patient_upload_id BIGINT UNSIGNED NULL AFTER approved_by_user_id,
    ADD KEY idx_mi_patient_visibility (clinic_id, patient_id, patient_visibility_status),
    ADD KEY idx_mi_patient_upload (clinic_id, uploaded_by_patient_upload_id);

-- FKs (idempotência manual: MySQL não tem IF NOT EXISTS para FK)
-- Se sua infra não permitir re-run com erro de FK duplicada, deixe como está e não reaplique esta migration.
ALTER TABLE medical_images
    ADD CONSTRAINT fk_mi_approved_by_user FOREIGN KEY (approved_by_user_id) REFERENCES users (id),
    ADD CONSTRAINT fk_mi_uploaded_by_patient_upload FOREIGN KEY (uploaded_by_patient_upload_id) REFERENCES patient_uploads (id);
