<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

/**
 * CsrfMiddleware — exige token CSRF en POST/PUT/PATCH/DELETE.
 *
 * Lectura del token (en este orden):
 *   1. Header `X-CSRF-Token` (preferido para JS/fetch).
 *   2. Campo del body `_csrf` (form HTML clásico).
 *
 * Validación con `Session::validateCsrf()` que usa `hash_equals` timing-safe.
 *
 * GET/HEAD/OPTIONS pasan sin chequeo (idempotentes).
 *
 * Para rutas API que usan Bearer (sin sesión), NO aplicar este middleware:
 * el Bearer ya autentica y no hay sesión a la que el navegador adjunte cookies
 * cross-site.
 */
final class CsrfMiddleware
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __invoke(Request $req, Closure $next): Response
    {
        if (in_array($req->method(), self::SAFE_METHODS, true)) {
            return $next($req);
        }

        $token = $req->header('x-csrf-token');
        if (!is_string($token) || $token === '') {
            $fromBody = $req->input('_csrf');
            $token = is_string($fromBody) ? $fromBody : '';
        }

        if ($token === '' || !Session::validateCsrf($token)) {
            return Response::json(
                ['error' => 'csrf_mismatch', 'message' => 'Falta o no coincide el token CSRF.'],
                403,
            );
        }

        return $next($req);
    }
}
