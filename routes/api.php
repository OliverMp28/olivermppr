<?php
declare(strict_types=1);

/**
 * Rutas JSON. Prefijo /api.
 *
 * Reglas de autenticación:
 *   - Endpoints "públicos del shell" (e.g. health, /api/me/token) NO usan
 *     AuthMiddleware: dependen de la sesión/cookies del navegador, no de
 *     Bearer. /api/me/token tiene su propio guard de sesión adentro.
 *   - Endpoints "de dominio" (Bloque 7+) sí usarán AuthMiddleware con Bearer.
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

    // Entrega del access_token al frontend (modo standalone). Auth = sesión.
    $r->get('/me/token', 'AuthController@meToken');
});
