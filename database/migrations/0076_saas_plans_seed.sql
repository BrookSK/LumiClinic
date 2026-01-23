SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT IGNORE INTO saas_plans (
    code, name,
    price_cents, currency,
    interval_unit, interval_count,
    trial_days,
    limits_json,
    status,
    created_at
) VALUES
(
    'trial', 'Trial',
    0, 'BRL',
    'month', 1,
    14,
    JSON_OBJECT(
        'users', 3,
        'patients', 500,
        'storage_mb', 512,
        'portal', true
    ),
    'active',
    NOW()
),
(
    'basic', 'Basic',
    9900, 'BRL',
    'month', 1,
    0,
    JSON_OBJECT(
        'users', 10,
        'patients', 3000,
        'storage_mb', 2048,
        'portal', true
    ),
    'active',
    NOW()
),
(
    'pro', 'Pro',
    19900, 'BRL',
    'month', 1,
    0,
    JSON_OBJECT(
        'users', 50,
        'patients', 20000,
        'storage_mb', 10240,
        'portal', true
    ),
    'active',
    NOW()
);
