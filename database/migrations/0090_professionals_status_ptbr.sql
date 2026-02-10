-- Migration: 0090_professionals_status_ptbr
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Padronizar status em PT-BR (ativo/inativo)

UPDATE professionals
SET status = 'ativo'
WHERE status = 'active';

UPDATE professionals
SET status = 'inativo'
WHERE status = 'inactive';

ALTER TABLE professionals
    MODIFY status VARCHAR(32) NOT NULL DEFAULT 'ativo';
