<?php

declare(strict_types=1);

namespace App\Core\View;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = []): string
    {
        $basePath = dirname(__DIR__, 2) . '/Views';
        $viewFile = $basePath . '/' . ltrim($view, '/') . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException("View not found: {$viewFile}");
        }

        extract($data);

        ob_start();
        require $viewFile;
        return (string)ob_get_clean();
    }
}
