<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

/**
 * Render mínimo de plantillas PHP en `app/Views/`. Sin Blade, sin Twig.
 *
 * Convenciones:
 *   - El nombre del template usa "." como separador: `pages.home` resuelve a
 *     `app/Views/pages/home.php`.
 *   - Para componer con un layout, la plantilla declara `$__layout` arriba:
 *
 *       <?php $__layout = 'layouts.main'; ?>
 *       <h1>Hola</h1>
 *
 *     Tras ejecutar la plantilla, View::render carga el layout y le pasa el
 *     output como variable `$content`. El layout puede a su vez declarar
 *     otro `$__layout` (recursión natural).
 *
 *   - `View::e($value)` es el escapador. Las plantillas deben usarlo para
 *     cualquier valor que venga de fuera (request, BD, env). El layout no
 *     escapa el `$content` — es HTML ya renderizado por View::render.
 */
final class View
{
    private const VIEWS_DIR = __DIR__ . '/../Views';

    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = []): string
    {
        $path = self::resolve($template);
        if (!is_file($path)) {
            throw new RuntimeException("View template no encontrado: {$template}");
        }

        $__layout = null;
        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $path;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        $rendered = (string) ob_get_clean();

        if (is_string($__layout) && $__layout !== '') {
            return self::render($__layout, array_merge($data, ['content' => $rendered]));
        }

        return $rendered;
    }

    /**
     * Escape HTML seguro. Usar SIEMPRE en las plantillas para valores externos.
     * El layout NO debe escapar `$content` — viene pre-renderizado por View.
     */
    public static function e(string|int|float|bool|null $value): string
    {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );
    }

    private static function resolve(string $template): string
    {
        // 'pages.home' → 'pages/home.php'. No permitimos `..` ni paths
        // absolutos: el template viene de código nuestro, no de input.
        $relative = str_replace('.', '/', $template);
        return self::VIEWS_DIR . '/' . $relative . '.php';
    }
}
