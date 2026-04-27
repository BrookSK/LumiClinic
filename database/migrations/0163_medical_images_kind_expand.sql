-- Migration: 0163_medical_images_kind_expand
-- Expande o ENUM da coluna kind para incluir photo, exam, progress
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET time_zone = '+00:00';

ALTER TABLE medical_images
    MODIFY COLUMN `kind` ENUM('before','after','other','photo','exam','progress') NOT NULL DEFAULT 'other';
