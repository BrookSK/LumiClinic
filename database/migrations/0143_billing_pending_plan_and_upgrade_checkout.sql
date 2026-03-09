SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE clinic_subscriptions
    ADD COLUMN pending_plan_id BIGINT UNSIGNED NULL AFTER plan_id,
    ADD COLUMN pending_plan_effective_at DATETIME NULL AFTER pending_plan_id,
    ADD COLUMN pending_upgrade_plan_id BIGINT UNSIGNED NULL AFTER pending_plan_effective_at,
    ADD COLUMN pending_upgrade_payment_id VARCHAR(190) NULL AFTER pending_upgrade_plan_id;

ALTER TABLE clinic_subscriptions
    ADD KEY idx_clinic_subscriptions_pending_plan (pending_plan_id),
    ADD KEY idx_clinic_subscriptions_pending_upgrade_plan (pending_upgrade_plan_id),
    ADD KEY idx_clinic_subscriptions_pending_upgrade_payment (pending_upgrade_payment_id);

ALTER TABLE clinic_subscriptions
    ADD CONSTRAINT fk_clinic_subscriptions_pending_plan FOREIGN KEY (pending_plan_id) REFERENCES saas_plans (id),
    ADD CONSTRAINT fk_clinic_subscriptions_pending_upgrade_plan FOREIGN KEY (pending_upgrade_plan_id) REFERENCES saas_plans (id);
