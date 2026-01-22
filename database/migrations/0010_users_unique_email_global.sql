-- Migration: 0010_users_unique_email_global
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Com clinic_id NULL (super admin), o UNIQUE (clinic_id, email) permite múltiplos NULL.
-- Para evitar duplicidade de e-mail no sistema, tornamos email globalmente único.

ALTER TABLE users
    DROP INDEX uq_users_clinic_email;

ALTER TABLE users
    ADD UNIQUE KEY uq_users_email (email);
