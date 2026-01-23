-- Migration: 0044_finance_sales_payments
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS sales (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NULL,

    total_bruto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    desconto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_liquido DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    status VARCHAR(32) NOT NULL DEFAULT 'open',
    origin VARCHAR(32) NOT NULL DEFAULT 'reception',

    notes VARCHAR(255) NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_sales_clinic_created (clinic_id, created_at),
    KEY idx_sales_status (status),
    KEY idx_sales_patient (patient_id),
    KEY idx_sales_deleted_at (deleted_at),

    CONSTRAINT fk_sales_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_sales_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_sales_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sale_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,

    type VARCHAR(32) NOT NULL,
    reference_id BIGINT UNSIGNED NOT NULL,

    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_sale_items_sale (sale_id),
    KEY idx_sale_items_type_ref (type, reference_id),
    KEY idx_sale_items_deleted_at (deleted_at),

    CONSTRAINT fk_sale_items_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,

    method VARCHAR(32) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',

    fees DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    gateway_ref VARCHAR(190) NULL,

    paid_at DATETIME NULL,

    created_by_user_id BIGINT UNSIGNED NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_payments_sale (sale_id),
    KEY idx_payments_clinic_created (clinic_id, created_at),
    KEY idx_payments_status (status),
    KEY idx_payments_deleted_at (deleted_at),

    CONSTRAINT fk_payments_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_payments_sale FOREIGN KEY (sale_id) REFERENCES sales (id),
    CONSTRAINT fk_payments_created_by FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sale_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,

    action VARCHAR(64) NOT NULL,
    meta_json JSON NOT NULL,

    actor_user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(64) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_sale_logs_sale (sale_id, id),
    KEY idx_sale_logs_clinic (clinic_id, id),

    CONSTRAINT fk_sale_logs_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_sale_logs_sale FOREIGN KEY (sale_id) REFERENCES sales (id),
    CONSTRAINT fk_sale_logs_actor FOREIGN KEY (actor_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
