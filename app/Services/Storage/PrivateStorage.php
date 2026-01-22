<?php

declare(strict_types=1);

namespace App\Services\Storage;

final class PrivateStorage
{
    public static function clinicBasePath(int $clinicId): string
    {
        $base = dirname(__DIR__, 3) . '/storage/private/clinic_' . $clinicId;
        if (!is_dir($base)) {
            mkdir($base, 0775, true);
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
            mkdir($dir, 0775, true);
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
