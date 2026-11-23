-- Migration: 0178_clinicorp_import_logs_skipped_details
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Guarda, para cada importação, os registros ignorados: linha da planilha, identificação e motivo.
ALTER TABLE clinicorp_import_logs
    ADD COLUMN skipped_json JSON NULL AFTER errors_json;
