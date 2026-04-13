-- Migration: 0161_services_color
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE services
    ADD COLUMN color VARCHAR(7) NULL AFTER price_cents;
