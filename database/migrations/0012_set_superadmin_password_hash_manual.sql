-- Migration: 0012_set_superadmin_password_hash_manual
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Gere um bcrypt válido com:
-- php -r "echo password_hash('SuperAdmin123!', PASSWORD_BCRYPT), PHP_EOL;"
-- e cole abaixo no lugar de __PASTE_BCRYPT_HASH_HERE__

UPDATE users
   SET password_hash = '$2y$12$picXbAI5QW33kyeezaahDugzcX187dmzW81Q5q8pQq8eQOofnBk4W',
       updated_at = NOW()
 WHERE email = 'superadmin@lumiclinic.local'
   AND deleted_at IS NULL;
