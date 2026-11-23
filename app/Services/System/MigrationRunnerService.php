<?php
declare(strict_types=1);

namespace App\Services\System;

use App\Core\Container\Container;

/**
 * Mesma logica do bin/migrate.php, para rodar pelo navegador (Super Admin > Servidor).
 * Usa a mesma tabela `_migrations`, entao CLI e web podem ser usados alternadamente.
 */
final class MigrationRunnerService
{
    public function __construct(private readonly Container $container) {}

    private function dir(): string
    {
        return dirname(__DIR__, 3) . '/database/migrations';
    }

    private function ensureTable(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `_migrations` (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                migration VARCHAR(255) NOT NULL,
                executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_migration_name (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /** @return array{total:int,executed:int,pending:list<string>,error:?string} */
    public function status(): array
    {
        $files = glob($this->dir() . '/*.sql') ?: [];
        sort($files);
        $names = array_map('basename', $files);

        try {
            $pdo = $this->container->get(\PDO::class);
            $this->ensureTable($pdo);
            $executed = array_flip($pdo->query('SELECT migration FROM `_migrations`')->fetchAll(\PDO::FETCH_COLUMN));
        } catch (\Throwable $e) {
            return ['total' => count($names), 'executed' => 0, 'pending' => [], 'error' => $e->getMessage()];
        }

        $pending = array_values(array_filter($names, fn ($n) => !isset($executed[$n])));

        return ['total' => count($names), 'executed' => count($names) - count($pending), 'pending' => $pending, 'error' => null];
    }

    /**
     * Executa as pendentes em ordem e para na primeira que falhar
     * (as seguintes podem depender dela).
     *
     * @return list<array{migration:string,ok:bool,message:string}>
     */
    public function runPending(): array
    {
        $pdo = $this->container->get(\PDO::class);
        $this->ensureTable($pdo);

        $results = [];
        foreach ($this->status()['pending'] as $name) {
            $sql = file_get_contents($this->dir() . '/' . $name);
            if ($sql === false || trim($sql) === '') {
                $results[] = ['migration' => $name, 'ok' => true, 'message' => 'Vazia (ignorada)'];
                continue;
            }

            try {
                // query + nextRowset faz o erro de qualquer comando do arquivo aparecer (exec so reporta o primeiro)
                $stmt = $pdo->query($sql);
                if ($stmt !== false) {
                    while ($stmt->nextRowset()) {
                    }
                    $stmt->closeCursor();
                }
                $pdo->prepare('INSERT INTO `_migrations` (migration) VALUES (:m)')->execute(['m' => $name]);
                $results[] = ['migration' => $name, 'ok' => true, 'message' => 'OK'];
            } catch (\Throwable $e) {
                $results[] = ['migration' => $name, 'ok' => false, 'message' => $e->getMessage()];
                break;
            }
        }

        // Algumas migrations mudam o fuso da sessao; volta ao padrao da aplicacao
        try {
            $pdo->exec("SET time_zone = '-03:00'");
        } catch (\Throwable $e) {
        }

        return $results;
    }
}
