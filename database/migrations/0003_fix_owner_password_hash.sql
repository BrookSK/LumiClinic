-- Migration: 0003_fix_owner_password_hash
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

-- SET NAMES utf8mb4;
-- SET time_zone = '+00:00';

-- -- Atualiza o hash do usuário Owner Demo para um bcrypt válido.
-- -- Senha: ChangeMe123!
-- UPDATE users
--    SET password_hash = '$2y$10$4CzV8c3D0e/0SIVQfWl6eOxz8qZc0D6nBq7v3J6Qb0u0k4mHk3b0y',
--        updated_at = NOW()
--  WHERE email = 'owner@demo.local'
--    AND deleted_at IS NULL;
