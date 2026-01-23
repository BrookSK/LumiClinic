-- Migration: 0048_financial_entries_cost_centers
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS cost_centers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(190) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_cost_centers_clinic_name (clinic_id, name),
    KEY idx_cost_centers_clinic (clinic_id),
    KEY idx_cost_centers_deleted_at (deleted_at),

    CONSTRAINT fk_cost_centers_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financial_entries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    kind VARCHAR(16) NOT NULL,
    occurred_on DATE NOT NULL,

    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    method VARCHAR(32) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'posted',

    cost_center_id BIGINT UNSIGNED NULL,
    sale_id BIGINT UNSIGNED NULL,
    payment_id BIGINT UNSIGNED NULL,

    description VARCHAR(255) NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_fin_entries_clinic_date (clinic_id, occurred_on),
    KEY idx_fin_entries_kind (kind),
    KEY idx_fin_entries_status (status),
    KEY idx_fin_entries_cost_center (cost_center_id),
    KEY idx_fin_entries_sale (sale_id),
    KEY idx_fin_entries_payment (payment_id),
    KEY idx_fin_entries_deleted_at (deleted_at),

    CONSTRAINT fk_fin_entries_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_fin_entries_cost_center FOREIGN KEY (cost_center_id) REFERENCES cost_centers (id),
    CONSTRAINT fk_fin_entries_sale FOREIGN KEY (sale_id) REFERENCES sales (id),
    CONSTRAINT fk_fin_entries_payment FOREIGN KEY (payment_id) REFERENCES payments (id),
    CONSTRAINT fk_fin_entries_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financial_entry_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    entry_id BIGINT UNSIGNED NOT NULL,

    action VARCHAR(64) NOT NULL,
    before_json JSON NULL,
    after_json JSON NULL,

    actor_user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(64) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_fin_entry_logs_entry (entry_id, id),
    KEY idx_fin_entry_logs_clinic (clinic_id, id),

    CONSTRAINT fk_fin_entry_logs_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_fin_entry_logs_entry FOREIGN KEY (entry_id) REFERENCES financial_entries (id),
    CONSTRAINT fk_fin_entry_logs_actor FOREIGN KEY (actor_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
