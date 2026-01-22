-- Migration: 0009_seed_super_admin
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Usuário Super Admin inicial (GLOBAL)
-- Email: superadmin@lumiclinic.local
-- Senha: SuperAdmin123!
-- Substitua o password_hash abaixo por um bcrypt real gerado com password_hash() se quiser.

INSERT INTO users (clinic_id, name, email, password_hash, is_super_admin, status, created_at)
VALUES (NULL, 'Super Admin', 'superadmin@lumiclinic.local', '$2y$12$2ARkjL8P.C.Yp.YchlLOtOGpqhudgr/N6PwnnWzKIkPQjv6Ypv8cu', 1, 'active', NOW());
