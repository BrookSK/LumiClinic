-- Migration: 0008_super_admin_users
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Permitir usuário global (super admin) sem clínica
ALTER TABLE users
    MODIFY clinic_id BIGINT UNSIGNED NULL;

ALTER TABLE users
    ADD COLUMN is_super_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;

CREATE INDEX idx_users_is_super_admin ON users (is_super_admin);
