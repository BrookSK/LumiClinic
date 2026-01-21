-- Migration: 0007_seed_demo_tenant_key
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Define o tenant_key para a clínica demo seedada na 0002
UPDATE clinics
   SET tenant_key = 'demo',
       updated_at = NOW()
 WHERE name = 'Clínica Demo'
   AND deleted_at IS NULL
   AND (tenant_key IS NULL OR tenant_key = '');
