-- Migration: 0096_appointments_patient_procedure_id
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE appointments
    ADD COLUMN patient_procedure_id BIGINT UNSIGNED NULL AFTER patient_id,
    ADD KEY idx_appointments_patient_procedure (patient_procedure_id),
    ADD CONSTRAINT fk_appointments_patient_procedure FOREIGN KEY (patient_procedure_id) REFERENCES patient_procedures (id);
