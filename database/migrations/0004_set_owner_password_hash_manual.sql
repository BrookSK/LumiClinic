-- Migration: 0004_set_owner_password_hash_manual
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Gere um bcrypt válido com:
-- php -r "echo password_hash('ChangeMe123!', PASSWORD_BCRYPT), PHP_EOL;"
-- e cole abaixo no lugar de __PASTE_BCRYPT_HASH_HERE__

UPDATE users
   SET password_hash = '$2y$12$2ARkjL8P.C.Yp.YchlLOtOGpqhudgr/N6PwnnWzKIkPQjv6Ypv8cu',
       updated_at = NOW()
 WHERE email = 'owner@demo.local'
   AND deleted_at IS NULL;
