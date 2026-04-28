<?php
declare(strict_types=1);

/**
 * Rutas web (HTML, sesión).
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
        <body><h1>Daino v2 — OK</h1><p>Bloque 3 (auth Vout) operativo.</p></body></html>
    HTML;
    return Response::html($body);
});

// --- OAuth flow contra Vout ----------------------------------------------
// CSRF aplica solo a POST/PUT/PATCH/DELETE. /auth/login y /auth/callback son
// GET — el `state` OAuth ya nos protege contra CSRF en esos.
$router->get('/auth/login',    'AuthController@showLogin');
$router->get('/auth/callback', 'AuthController@callback');
$router->post('/auth/logout',  'AuthController@logout')->middleware('CsrfMiddleware');
$router->post('/auth/refresh', 'AuthController@refresh')->middleware('CsrfMiddleware');
