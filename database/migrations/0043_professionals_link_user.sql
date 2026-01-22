-- Migration: 0043_professionals_link_user
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE professionals
    ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER clinic_id,
    ADD KEY idx_professionals_user_id (user_id),
    ADD UNIQUE KEY uq_professionals_clinic_user (clinic_id, user_id),
    ADD CONSTRAINT fk_professionals_user FOREIGN KEY (user_id) REFERENCES users (id);
