-- Migration: 0158_anamnesis_requests_nullable_appointment
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.
-- Permite envio de anamnese sem agendamento vinculado (ex: envio manual pelo painel).

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE appointment_anamnesis_requests
    DROP FOREIGN KEY fk_a_ar_appointment,
    MODIFY COLUMN appointment_id BIGINT UNSIGNED NULL,
    ADD CONSTRAINT fk_a_ar_appointment_v2 FOREIGN KEY (appointment_id) REFERENCES appointments (id);
