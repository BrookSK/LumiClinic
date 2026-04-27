-- Migration: 0164_document_sign_requests
-- Solicitações de assinatura de documentos enviados ao paciente
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS document_sign_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    body TEXT NULL,
    file_path VARCHAR(500) NULL,
    file_name VARCHAR(255) NULL,
    file_mime VARCHAR(100) NULL,

    token VARCHAR(128) NOT NULL,
    status ENUM('pending','signed','expired','cancelled') NOT NULL DEFAULT 'pending',

    sent_via VARCHAR(50) NULL,
    sent_at DATETIME NULL,

    signature_data MEDIUMTEXT NULL,
    signed_at DATETIME NULL,
    signed_ip VARCHAR(45) NULL,
    signed_user_agent VARCHAR(500) NULL,

    created_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_doc_sign_token (token),
    KEY idx_doc_sign_clinic (clinic_id),
    KEY idx_doc_sign_patient (clinic_id, patient_id),
    KEY idx_doc_sign_status (status),
    CONSTRAINT fk_doc_sign_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_doc_sign_patient FOREIGN KEY (patient_id) REFERENCES patients (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
