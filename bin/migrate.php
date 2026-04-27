<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Migration Runner
|--------------------------------------------------------------------------
| Executa todas as migrations SQL em database/migrations/ em ordem numérica.
| Mantém controle na tabela `_migrations` para não rodar a mesma duas vezes.
|
| Uso: php bin/migrate.php
*/

$dbConfig = require dirname(__DIR__) . '/config/database.php';

try {
    $pdo = new \PDO(
        $dbConfig['dsn'],
        $dbConfig['username'],
        $dbConfig['password'],
        [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]
    );
} catch (\Throwable $e) {
    fwrite(STDERR, "Erro ao conectar no banco: " . $e->getMessage() . "\n");
    exit(1);
}

echo "Conectado ao banco com sucesso.\n";

// Criar tabela de controle de migrations se não existir
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `_migrations` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        migration VARCHAR(255) NOT NULL,
        executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_migration_name (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Buscar migrations já executadas
$executed = [];
$stmt = $pdo->query("SELECT migration FROM `_migrations`");
while ($row = $stmt->fetch()) {
    $executed[$row['migration']] = true;
}

// Listar arquivos de migration
$migrationsDir = dirname(__DIR__) . '/database/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

$ran = 0;
$skipped = 0;
$errors = 0;

foreach ($files as $file) {
    $name = basename($file);

    if (isset($executed[$name])) {
        $skipped++;
        continue;
    }

    echo "Rodando: {$name} ... ";

    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        echo "VAZIO (pulando)\n";
        continue;
    }

    try {
        $pdo->exec($sql);
        $pdo->prepare("INSERT INTO `_migrations` (migration) VALUES (:m)")->execute(['m' => $name]);
        echo "OK\n";
        $ran++;
    } catch (\Throwable $e) {
        echo "ERRO: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n";
echo "Concluído: {$ran} executada(s), {$skipped} já rodada(s), {$errors} erro(s).\n";

exit($errors > 0 ? 1 : 0);
