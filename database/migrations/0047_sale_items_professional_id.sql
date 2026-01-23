-- Migration: 0047_sale_items_professional_id
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE sale_items
    ADD COLUMN professional_id BIGINT UNSIGNED NULL AFTER reference_id,
    ADD KEY idx_sale_items_professional (professional_id),
    ADD CONSTRAINT fk_sale_items_professional FOREIGN KEY (professional_id) REFERENCES professionals (id);
