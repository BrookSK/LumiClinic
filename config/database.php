<?php

declare(strict_types=1);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$db = getenv('DB_DATABASE') ?: 'lumiclinic';
$charset = 'utf8mb4';

return [
    'dsn' => "mysql:host={$host};port={$port};dbname={$db};charset={$charset}",
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
];
