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
];
