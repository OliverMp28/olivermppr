<?php
declare(strict_types=1);

/**
 * Rutas web (HTML, sesión). Sin prefijo. CSRF se aplicará vía middleware
 * cuando aparezcan rutas POST en bloques posteriores.
 *
 * El front controller (public/index.php) ya creó $router. NO crear uno nuevo.
 *
 * @var \App\Core\Router $router
 */

use App\Core\Response;

$router->get('/', static function (): Response {
    $body = <<<'HTML'
        <!doctype html>
        <html lang="es"><head><meta charset="utf-8"><title>Daino v2</title></head>
        <body><h1>Daino v2 — OK</h1><p>Bloque 1 (mini-framework MVC) operativo.</p></body></html>
    HTML;
    return Response::html($body);
});
