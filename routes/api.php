<?php
declare(strict_types=1);

/**
 * Rutas JSON. Prefijo /api. Sin sesión ni CSRF — la API usa Bearer token
 * (Bloque 3) para autenticación.
 *
 * @var \App\Core\Router $router
 */

use App\Core\Response;
use App\Core\Router;

$router->group(['prefix' => '/api'], static function (Router $r): void {
    $r->get('/health', static fn (): Response => Response::json([
        'status' => 'ok',
        'php'    => PHP_VERSION,
        'time'   => date('c'),
    ]));
});
