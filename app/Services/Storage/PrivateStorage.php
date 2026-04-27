<?php

declare(strict_types=1);

namespace App\Services\Storage;

final class PrivateStorage
{
    private static function storageBase(): string
    {
        return dirname(__DIR__, 3) . '/storage/private';
    }

    /**
     * Garante que o diretório base storage/private existe.
     */
    private static function ensureStorageBase(): void
    {
        $base = self::storageBase();
        if (!is_dir($base)) {
            @mkdir($base, 0775, true);
        }
    }

    public static function clinicBasePath(int $clinicId): string
    {
        self::ensureStorageBase();

        $base = self::storageBase() . '/clinic_' . $clinicId;
        if (!is_dir($base)) {
            if (!@mkdir($base, 0775, true) && !is_dir($base)) {
                error_log('[PrivateStorage] Falha ao criar diretório: ' . $base
                    . ' — verifique as permissões da pasta storage/');
            }
        }
        return $base;
    }

    public static function put(int $clinicId, string $relativePath, string $bytes): string
    {
        $base = self::clinicBasePath($clinicId);
        $relativePath = ltrim($relativePath, '/');
        $full = $base . '/' . $relativePath;

        $dir = dirname($full);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                error_log('[PrivateStorage] Falha ao criar diretório: ' . $dir
                    . ' — verifique as permissões da pasta storage/');
            }
        }

        file_put_contents($full, $bytes);

        return $full;
    }

    public static function fullPath(int $clinicId, string $relativePath): string
    {
        $base = self::clinicBasePath($clinicId);
        $relativePath = ltrim($relativePath, '/');
        return $base . '/' . $relativePath;
    }
}
