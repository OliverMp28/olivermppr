<?php
/**
 * Layout HTML5 base para todas las páginas server-rendered.
 *
 * Contrato de variables (todas pasadas por View::render):
 *   - string $title         Título de la pestaña.
 *   - string $voutOrigin    Origin completo de Vout (ej. https://vout.local).
 *                           Lo lee bridge.js para validar postMessage. Si está
 *                           vacío, el bridge ignorará todos los mensajes — útil
 *                           en standalone donde no hay parent.
 *   - int    $uploadMaxMb   Tamaño máximo permitido para drag&drop de MP3.
 *                           Lo lee resources/js/ui/upload.js.
 *   - string $content       HTML pre-renderizado de la página hija. NO escapar.
 *
 * Estructura visual (consistente con docs 06):
 *   <body>
 *     <div id="app"></div>           ← canvas Pixi (engine.js lo append-ea aquí)
 *     <div id="hint">…</div>         ← overlay drag&drop, oculto cuando audio:ready
 *     <?= $content ?>                ← contenido específico de la página (ahora vacío)
 *   </body>
 */

use App\Core\View;
use App\Core\ViteAssets;

/** @var string $title */
/** @var string $voutOrigin */
/** @var int $uploadMaxMb */
/** @var string $content */
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="dark">
    <meta name="referrer" content="strict-origin-when-cross-origin">

    <!-- Contrato con resources/js/iframe/bridge.js: validación de event.origin
         en postMessage. Vacío en standalone = el bridge no atiende mensajes. -->
    <meta name="daino:vout-origin" content="<?= View::e($voutOrigin) ?>">

    <!-- Contrato con resources/js/ui/upload.js: tamaño máximo de MP3. -->
    <meta name="daino:upload-max-mb" content="<?= View::e($uploadMaxMb) ?>">

    <title><?= View::e($title) ?></title>

    <?= ViteAssets::render(['resources/js/main.js', 'resources/css/main.css']) ?>
</head>
<body>
    <div id="app" aria-hidden="true"></div>

    <div id="hint" data-hint="drop" role="status" aria-live="polite">
        <span class="hint-title">Suelta tu MP3 aquí</span>
        <span class="hint-sub">o arrástralo desde tu carpeta</span>
    </div>

    <?= $content ?>
</body>
</html>
