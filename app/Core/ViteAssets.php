<?php
declare(strict_types=1);

namespace App\Core;

use JsonException;
use RuntimeException;

/**
 * Puente entre Vite y las vistas PHP.
 *
 * Modo desarrollo  (env VITE_DEV_SERVER definida): emite <script type="module">
 *   apuntando al dev server de Vite (ej. http://localhost:5173) + el cliente HMR.
 *
 * Modo producción (VITE_DEV_SERVER vacía): lee public/build/manifest.json y
 *   devuelve los <link>/<script> con los nombres de archivo hasheados que
 *   generó `bun run build`.
 */
final class ViteAssets
{
    private const MANIFEST_PATH = __DIR__ . '/../../public/build/manifest.json';

    /**
     * @param list<string> $entries Rutas de entrada tal como están en vite.config.js
     *                              (ej. 'resources/js/main.js', 'resources/css/main.css').
     */
    public static function render(array $entries): string
    {
        $devServer = $_ENV['VITE_DEV_SERVER'] ?? getenv('VITE_DEV_SERVER') ?: '';

        if ($devServer !== '') {
            return self::renderDev($entries, rtrim($devServer, '/'));
        }

        return self::renderProd($entries);
    }

    /**
     * @param list<string> $entries
     */
    private static function renderDev(array $entries, string $devServer): string
    {
        $tags = sprintf(
            '<script type="module" src="%s/@vite/client"></script>' . "\n",
            htmlspecialchars($devServer, ENT_QUOTES)
        );

        foreach ($entries as $entry) {
            $tags .= sprintf(
                '<script type="module" src="%s/%s"></script>' . "\n",
                htmlspecialchars($devServer, ENT_QUOTES),
                htmlspecialchars(ltrim($entry, '/'), ENT_QUOTES)
            );
        }

        return $tags;
    }

    /**
     * @param list<string> $entries
     */
    private static function renderProd(array $entries): string
    {
        if (!is_file(self::MANIFEST_PATH)) {
            return '<!-- Vite manifest no encontrado: ejecuta `bun run build` -->' . "\n";
        }

        try {
            /** @var array<string, array{file: string, css?: list<string>}> $manifest */
            $manifest = json_decode(
                (string) file_get_contents(self::MANIFEST_PATH),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            throw new RuntimeException('Vite manifest no parseable: ' . $e->getMessage(), 0, $e);
        }

        $tags = '';

        foreach ($entries as $entry) {
            if (!isset($manifest[$entry])) {
                $tags .= sprintf(
                    '<!-- Vite entry "%s" ausente del manifest -->' . "\n",
                    htmlspecialchars($entry, ENT_QUOTES)
                );
                continue;
            }

            $file = $manifest[$entry]['file'];
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            $tags .= $ext === 'css'
                ? sprintf('<link rel="stylesheet" href="/build/%s">' . "\n", htmlspecialchars($file, ENT_QUOTES))
                : sprintf('<script type="module" src="/build/%s"></script>' . "\n", htmlspecialchars($file, ENT_QUOTES));

            foreach ($manifest[$entry]['css'] ?? [] as $cssFile) {
                $tags .= sprintf(
                    '<link rel="stylesheet" href="/build/%s">' . "\n",
                    htmlspecialchars($cssFile, ENT_QUOTES)
                );
            }
        }

        return $tags;
    }
}
