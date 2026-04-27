<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Database Configuration with Fallback
|--------------------------------------------------------------------------
| Tenta conectar com a configuração primária. Se falhar, usa a secundária.
*/

$primary = [
    'dsn' => 'mysql:host=localhost;port=3306;dbname=bd_lumini_prod;charset=utf8mb4',
    'username' => 'bd_lumini_prod',
    'password' => 'bd_lumini_prod124536',
];

$fallback = [
    'dsn' => 'mysql:host=localhost;port=3306;dbname=db_9_bd_lumini_prod;charset=utf8mb4',
    'username' => 'bd_lumini_prod',
    'password' => 'bd_lumini_prod124536',
];

try {
    $pdo = new \PDO(
        $primary['dsn'],
        $primary['username'],
        $primary['password'],
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
    );
    unset($pdo);

    return $primary;
} catch (\Throwable $e) {
    return $fallback;
}
