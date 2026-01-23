SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE billing_events
    MODIFY clinic_id BIGINT UNSIGNED NULL;
