<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'LumiClinic',
        // 'base_url' => getenv('APP_BASE_URL') ?: 'http://localhost:8000',
        'base_url' => getenv('APP_BASE_URL') ?: (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
            . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        ),

        'env' => getenv('APP_ENV') ?: 'local',
    ],
    'billing' => [
        'asaas' => [
            'base_url' => getenv('ASAAS_BASE_URL') ?: 'https://www.asaas.com/api/v3',
            'api_key' => getenv('ASAAS_API_KEY') ?: '',
            'billing_type' => getenv('ASAAS_BILLING_TYPE') ?: 'BOLETO',
            'webhook_secret' => getenv('ASAAS_WEBHOOK_SECRET') ?: '',
        ],
        'mercadopago' => [
            'base_url' => getenv('MP_BASE_URL') ?: 'https://api.mercadopago.com',
            'access_token' => getenv('MP_ACCESS_TOKEN') ?: '',
            'payer_email_default' => getenv('MP_PAYER_EMAIL_DEFAULT') ?: '',
            'webhook_secret' => getenv('MP_WEBHOOK_SECRET') ?: '',
        ],
    ],
    'session' => [
        'name' => 'lumiclinic_session',
        'name_patient' => 'lumiclinic_patient_session',
        'secure' => (bool)(getenv('SESSION_SECURE') ?: false),
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    'csrf' => [
        'enabled' => true,
        'token_key' => '_csrf',
    ],
    'observability' => [
        'retention_days_event_logs' => (int)(getenv('OBS_RETENTION_EVENT_DAYS') ?: 90),
        'retention_days_performance_logs' => (int)(getenv('OBS_RETENTION_PERF_DAYS') ?: 30),
        'retention_days_system_metrics' => (int)(getenv('OBS_RETENTION_METRICS_DAYS') ?: 365),
        'event_payload_max_bytes' => (int)(getenv('OBS_EVENT_PAYLOAD_MAX_BYTES') ?: 16384),
    ],
    'private' => [
        'tutorial_password' => 'lumiclinic',
    ],
];
