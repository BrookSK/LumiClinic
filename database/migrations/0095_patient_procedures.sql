-- Migration: 0095_patient_procedures
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS patient_procedures (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    service_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED NULL,

    sale_id BIGINT UNSIGNED NULL,
    sale_item_id BIGINT UNSIGNED NULL,

    total_sessions INT NOT NULL DEFAULT 1,
    used_sessions INT NOT NULL DEFAULT 0,

    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uniq_patient_procedures_sale_item (clinic_id, sale_item_id),
    KEY idx_patient_procedures_patient (clinic_id, patient_id, status),
    KEY idx_patient_procedures_sale (clinic_id, sale_id),
    KEY idx_patient_procedures_service (clinic_id, service_id),
    KEY idx_patient_procedures_deleted_at (deleted_at),

    CONSTRAINT fk_patient_procedures_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_patient_procedures_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_patient_procedures_service FOREIGN KEY (service_id) REFERENCES services (id),
    CONSTRAINT fk_patient_procedures_professional FOREIGN KEY (professional_id) REFERENCES professionals (id),
    CONSTRAINT fk_patient_procedures_sale FOREIGN KEY (sale_id) REFERENCES sales (id),
    CONSTRAINT fk_patient_procedures_sale_item FOREIGN KEY (sale_item_id) REFERENCES sale_items (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
