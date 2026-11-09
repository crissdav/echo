<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Renderizado de vistas PHP dentro de un layout.
 */
final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'app'): void
    {
        $content = self::partial($view, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::partial('layouts/' . $layout, $data + ['content' => $content]);
    }

    public static function partial(string $view, array $data = []): string
    {
        $file = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Vista no encontrada: $view");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
