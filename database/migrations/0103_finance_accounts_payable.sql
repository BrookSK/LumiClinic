-- Migration: 0103_finance_accounts_payable
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS accounts_payable (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    vendor_name VARCHAR(140) NULL,
    title VARCHAR(140) NOT NULL,
    description TEXT NULL,

    cost_center_id BIGINT UNSIGNED NULL,

    payable_type VARCHAR(32) NOT NULL DEFAULT 'single',
    status VARCHAR(32) NOT NULL DEFAULT 'active',

    start_due_date DATE NOT NULL,

    total_installments INT NULL,
    recurrence_interval VARCHAR(16) NULL,
    recurrence_until DATE NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_ap_clinic_status (clinic_id, status, start_due_date),
    KEY idx_ap_clinic_cost_center (clinic_id, cost_center_id),
    KEY idx_ap_deleted_at (deleted_at),

    CONSTRAINT fk_ap_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_ap_cost_center FOREIGN KEY (cost_center_id) REFERENCES cost_centers (id),
    CONSTRAINT fk_ap_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accounts_payable_installments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    payable_id BIGINT UNSIGNED NOT NULL,

    installment_no INT NOT NULL DEFAULT 1,
    due_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'open',
    paid_at DATETIME NULL,
    paid_entry_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_ap_inst_payable_no (clinic_id, payable_id, installment_no),
    KEY idx_ap_inst_due (clinic_id, due_date, status),
    KEY idx_ap_inst_paid_entry (paid_entry_id),
    KEY idx_ap_inst_deleted_at (deleted_at),

    CONSTRAINT fk_ap_inst_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_ap_inst_payable FOREIGN KEY (payable_id) REFERENCES accounts_payable (id),
    CONSTRAINT fk_ap_inst_paid_entry FOREIGN KEY (paid_entry_id) REFERENCES financial_entries (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
