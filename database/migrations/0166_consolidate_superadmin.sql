-- Migration: 0166_consolidate_superadmin
-- Remove todos os super admins duplicados e garante apenas um único
-- com email superadmin@lumiclinic.com.br e senha Lumi#2025#Clinic
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- 1. Remover is_super_admin de todos os usuários existentes
UPDATE users SET is_super_admin = 0 WHERE is_super_admin = 1;

-- 2. Remover (soft delete) todos os usuários com email de superadmin antigos
--    para evitar conflito de email único
UPDATE users
SET deleted_at = NOW()
WHERE email IN (
    'superadmin@lumiclinic.local',
    'superadmin@lumiclinic.com.br'
)
AND deleted_at IS NULL;

-- 3. Inserir o único super admin oficial
INSERT INTO users (
    name,
    email,
    password_hash,
    is_super_admin,
    clinic_id,
    status,
    created_at
) VALUES (
    'Super Admin',
    'superadmin@lumiclinic.com.br',
    '$2y$12$G8QCjeQ3iMlRtptJR2mEsedU3AEGRGJBLOvblH74pqQj3y53pt/jK',
    1,
    NULL,
    'active',
    NOW()
);
