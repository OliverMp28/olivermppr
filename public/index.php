<?php
declare(strict_types=1);

/**
 * Front controller de Daino.
 *
 * Nginx reescribe todas las peticiones HTTP a este archivo. Es el ÚNICO
 * .php accesible públicamente; todo lo demás vive fuera de /public.
 *
 * Por ahora solo verifica que el pipeline PHP+Nginx+Vite funciona. Cuando
 * exista app/Core/Router.php, sustituimos el render inline por:
 *     (new App\Core\Router())->dispatch($_SERVER['REQUEST_URI']);
 */

const DAINO_ROOT = __DIR__ . '/..';

require DAINO_ROOT . '/app/bootstrap.php';

use App\Core\ViteAssets;

header('Content-Type: text/html; charset=utf-8');

$viteTags = ViteAssets::render([
    'resources/js/main.js',
    'resources/css/main.css',
]);
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Daino v2 — scaffolding</title>
    <?= $viteTags ?>
</head>
<body>
    <div id="app">
        <main class="flex h-full w-full flex-col items-center justify-center gap-4 text-center">
            <h1 class="text-4xl font-bold tracking-tight">Daino v2</h1>
            <p class="text-sm text-neutral-400">scaffolding OK — pipeline arriba</p>
            <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-xs text-neutral-500">
                <dt class="text-right">PHP</dt><dd class="text-left"><?= htmlspecialchars(PHP_VERSION, ENT_QUOTES) ?></dd>
                <dt class="text-right">Hora servidor</dt><dd class="text-left"><?= htmlspecialchars(date('c'), ENT_QUOTES) ?></dd>
                <dt class="text-right">Entorno</dt><dd class="text-left"><?= htmlspecialchars($_ENV['APP_ENV'] ?? 'unset', ENT_QUOTES) ?></dd>
            </dl>
        </main>
    </div>
</body>
</html>
