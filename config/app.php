<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'LumiClinic',
        'base_url' => getenv('APP_BASE_URL') ?: 'http://localhost:8000',
        'env' => getenv('APP_ENV') ?: 'local',
    ],
    'session' => [
        'name' => 'lumiclinic_session',
        'secure' => (bool)(getenv('SESSION_SECURE') ?: false),
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    'csrf' => [
        'enabled' => true,
        'token_key' => '_csrf',
    ],
];
