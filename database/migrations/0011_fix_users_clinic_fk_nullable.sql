-- Migration: 0011_fix_users_clinic_fk_nullable
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Alguns MySQL exigem remover a FK antes de alterar a nulabilidade.
-- Esta migration garante clinic_id NULL (para super admin global) mantendo FK.

ALTER TABLE users
    DROP FOREIGN KEY fk_users_clinic;

ALTER TABLE users
    MODIFY clinic_id BIGINT UNSIGNED NULL;

ALTER TABLE users
    ADD CONSTRAINT fk_users_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id);
