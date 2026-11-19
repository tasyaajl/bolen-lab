<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $viewFile = BASE_PATH . '/app/Views/' . $view . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException('View tidak ditemukan: ' . $view);
        }

        $data['settings'] = $data['settings'] ?? \App\Models\Setting::all();

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        if ($layout === null) {
            return $content;
        }

        $layoutFile = BASE_PATH . '/app/Views/' . $layout . '.php';

        if (!is_file($layoutFile)) {
            throw new RuntimeException('Layout tidak ditemukan: ' . $layout);
        }

        ob_start();
        require $layoutFile;

        return (string) ob_get_clean();
    }
}
