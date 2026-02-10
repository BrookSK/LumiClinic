-- Migration: 0086_users_email_per_clinic
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Reverte UNIQUE global de email (permitir mesmo e-mail em múltiplas clínicas)
-- Mantém unicidade por clínica.

ALTER TABLE users
    DROP INDEX uq_users_email;

ALTER TABLE users
    ADD UNIQUE KEY uq_users_clinic_email (clinic_id, email);
