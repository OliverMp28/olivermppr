<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\JwtVerifier;
use Closure;

/**
 * AuthMiddleware — gating de rutas autenticadas.
 *
 *  - Rutas API (path empieza por `/api/`): exige `Authorization: Bearer <jwt>`
 *    válido. Falla con 401 JSON si falta o no verifica.
 *  - Rutas web: exige sesión iniciada (`vout_id` en `$_SESSION`). Si falta,
 *    redirige a `/auth/login`.
 *
 * NO almacena claims en ningún sitio compartido — los controllers leen lo que
 * necesiten desde `$_SESSION` (web) o re-validan el JWT (API). Si en el futuro
 * Bloque 7 necesita claims propagados, se añade un AuthContext minimal aquí.
 *
 * El JwtVerifier es inyectable para tests; por defecto se construye con
 * `JwtVerifier::fromEnv()` la primera vez que se necesita.
 */
final class AuthMiddleware
{
    public function __construct(
        private readonly ?JwtVerifier $verifier = null,
    ) {
    }

    public function __invoke(Request $req, Closure $next): Response
    {
        $isApi = str_starts_with($req->path(), '/api/');

        if ($isApi) {
            $token = $req->bearerToken();
            if ($token === null) {
                return Response::json(
                    ['error' => 'unauthorized', 'message' => 'Missing Bearer token.'],
                    401,
                );
            }
            try {
                ($this->verifier ?? JwtVerifier::fromEnv())->verify($token);
            } catch (\Throwable $e) {
                return Response::json(
                    ['error' => 'invalid_token', 'message' => $e->getMessage()],
                    401,
                );
            }
            return $next($req);
        }

        // Web mode
        if (!Session::has('vout_id')) {
            return Response::redirect('/auth/login');
        }
        return $next($req);
    }
}
