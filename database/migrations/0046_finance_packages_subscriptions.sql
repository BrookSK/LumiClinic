-- Migration: 0046_finance_packages_subscriptions
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS packages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(190) NOT NULL,
    service_id BIGINT UNSIGNED NULL,

    total_sessions INT NOT NULL DEFAULT 1,
    validity_days INT NOT NULL DEFAULT 0,

    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_packages_clinic (clinic_id),
    KEY idx_packages_service (service_id),
    KEY idx_packages_deleted_at (deleted_at),

    CONSTRAINT fk_packages_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_packages_service FOREIGN KEY (service_id) REFERENCES services (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_packages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    package_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NULL,
    sale_item_id BIGINT UNSIGNED NULL,

    total_sessions INT NOT NULL DEFAULT 1,
    used_sessions INT NOT NULL DEFAULT 0,

    valid_until DATE NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_patient_packages_patient (patient_id),
    KEY idx_patient_packages_valid_until (valid_until),
    KEY idx_patient_packages_deleted_at (deleted_at),

    CONSTRAINT fk_patient_packages_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_patient_packages_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_patient_packages_package FOREIGN KEY (package_id) REFERENCES packages (id),
    CONSTRAINT fk_patient_packages_sale FOREIGN KEY (sale_id) REFERENCES sales (id),
    CONSTRAINT fk_patient_packages_sale_item FOREIGN KEY (sale_item_id) REFERENCES sale_items (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_plans (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(190) NOT NULL,

    interval_months INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    status VARCHAR(32) NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_subscription_plans_clinic (clinic_id),
    KEY idx_subscription_plans_deleted_at (deleted_at),

    CONSTRAINT fk_subscription_plans_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_subscriptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    plan_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NULL,
    sale_item_id BIGINT UNSIGNED NULL,

    status VARCHAR(32) NOT NULL DEFAULT 'active',

    started_at DATE NULL,
    ends_at DATE NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_patient_subscriptions_patient (patient_id),
    KEY idx_patient_subscriptions_status (status),
    KEY idx_patient_subscriptions_deleted_at (deleted_at),

    CONSTRAINT fk_patient_subscriptions_clinic FOREIGN KEY (clinic_id) REFERENCES clinics (id),
    CONSTRAINT fk_patient_subscriptions_patient FOREIGN KEY (patient_id) REFERENCES patients (id),
    CONSTRAINT fk_patient_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans (id),
    CONSTRAINT fk_patient_subscriptions_sale FOREIGN KEY (sale_id) REFERENCES sales (id),
    CONSTRAINT fk_patient_subscriptions_sale_item FOREIGN KEY (sale_item_id) REFERENCES sale_items (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
