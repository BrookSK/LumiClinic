<?php

declare(strict_types=1);

namespace App\Services\Storage;

final class PrivateStorage
{
    private static function storageBase(): string
    {
        return dirname(__DIR__, 3) . '/storage/private';
    }

    private static function ensureDir(string $dir): bool
    {
        if (is_dir($dir)) {
            return true;
        }

        if (@mkdir($dir, 0775, true)) {
            return true;
        }

        // mkdir pode falhar mas outro processo criou ao mesmo tempo
        if (is_dir($dir)) {
            return true;
        }

        error_log('[PrivateStorage] Não foi possível criar: ' . $dir
            . ' — execute no servidor: sudo chown -R www-data:www-data '
            . self::storageBase() . ' && sudo chmod -R 775 ' . self::storageBase());

        return false;
    }

    public static function clinicBasePath(int $clinicId): string
    {
        $base = self::storageBase() . '/clinic_' . $clinicId;
        self::ensureDir($base);
        return $base;
    }

    public static function put(int $clinicId, string $relativePath, string $bytes): string|false
    {
        $base = self::clinicBasePath($clinicId);
        $relativePath = ltrim($relativePath, '/');
        $full = $base . '/' . $relativePath;

        $dir = dirname($full);
        if (!self::ensureDir($dir)) {
            return false;
        }

        if (@file_put_contents($full, $bytes) === false) {
            error_log('[PrivateStorage] Falha ao gravar arquivo: ' . $full);
            return false;
        }

        return $full;
    }

    public static function fullPath(int $clinicId, string $relativePath): string
    {
        $base = self::clinicBasePath($clinicId);
        $relativePath = ltrim($relativePath, '/');
        return $base . '/' . $relativePath;
    }
}
